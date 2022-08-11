#
# Wrapper script for running app through the iReceptor Gateway.
# 

# Get the script directory where all the code is.
SCRIPT_DIR=`pwd`
echo "Running job from ${SCRIPT_DIR}"

########################################################################
# Tapis configuration/settings
########################################################################

#
# Tapis/AGAVE job variables.
#

AGAVE_JOB_ID=${AGAVE_JOB_ID}
AGAVE_JOB_NAME=${AGAVE_JOB_NAME}
AGAVE_LOG_NAME=${AGAVE_JOB_NAME}-${AGAVE_JOB_ID}
AGAVE_JOB_PROCESSORS_PER_NODE=${AGAVE_JOB_PROCESSORS_PER_NODE}
AGAVE_JOB_MEMORY_PER_NODE=${AGAVE_JOB_MEMORY_PER_NODE}

#
# Tapis App Parameters: Will be subsituted by Tapis. If they don't exist
# use command line arguments so we can test from the command line.
#

# We pass a singularity image to get from the Gateway. This image is provided
# on the Gateway because we only want to run singularity images that are approved
# by the gateway.
singularity_image="${singularity_image}"
echo "Singularity image = ${singularity_image}"

#
# Tapis App Inputs
#

# Download file is a ZIP archive that is provided by the Gateway and contains
# the results of the users query. This is the data that is being analyzed.
if [ -z "${download_file}" ]; then
        ZIP_FILE=$1
else
        ZIP_FILE=${download_file}
fi

# Many of our TAPIS Apps have a split_reperotire variable, so to keep thinks
# consistent we define it here if it isn't provided by the App.
# Immunarch by default splits repertoires.
if [ -z "${split_repertoire}" ]; then
        split_repertoire="True"
fi

# If you want to tell Tapis that the job failed
export JOB_ERROR=1

########################################################################
# Done Tapis setup/processing.
########################################################################

GATEWAY_URL=https://gateway-analysis-dev.ireceptor.org
# Get the singularity image from the Gateway
echo "Downloading singularity image from Gateway ${GATEWAY_URL}"
date
wget -nv ${GATEWAY_URL}/singularity/${singularity_image}
echo -n "Singularity file downloaded = "
ls ${singularity_image}
echo "Done ownloading singularity image from the Gateway"
date

# Get the iRecpetor Gateway utilities from the Gateway
echo "Downloading iReceptor Gateway Utilities from the Gateway"
date
GATEWAY_UTIL_DIR=gateway_utilities
mkdir -p ${GATEWAY_UTIL_DIR}
pushd ${GATEWAY_UTIL_DIR} > /dev/null
wget --no-verbose -r -nH --no-parent --cut-dir=1 --reject="index.html*" --reject="robots.txt*" ${GATEWAY_URL}/gateway_utilities/
popd > /dev/null
echo "Done downloading iReceptor Gateway Utilities"
date

# Load the iReceptor Gateway bash utility functions.
source ${SCRIPT_DIR}/${GATEWAY_UTIL_DIR}/gateway_utilities.sh
# This directory is defined in the gateway_utilities.sh. The Gateway
# relies on this being set. If it isn't set, abort as something has
# gone wrong with loading the Gateway utilties.
echo "Gateway analysis directory = ${GATEWAY_ANALYSIS_DIR}"
if [ -z "${GATEWAY_ANALYSIS_DIR}" ]; then
	echo "ERROR: GATEWAY_ANALYSIS_DIR not defined, gateway_utilities not loaded correctly."
        exit 1
fi
echo "Done loading iReceptor Gateway Utilities"


# Load any modules that are required by the App. 
module load singularity
module load scipy-stack

# The Gateway provides information about the download in the file info.txt
INFO_FILE="info.txt"
MANIFEST_FILE="AIRR-manifest.json"

# Start
printf "START at $(date)\n\n"
printf "PROCS = ${AGAVE_JOB_PROCESSORS_PER_NODE}\n\n"
printf "MEM = ${AGAVE_JOB_MEMORY_PER_NODE}\n\n"

# This function is called by the iReceptor Gateway utilities function gateway_split_repertoire
# The gateway utility function splits all data into repertoires and then calls this function
# for a single repertoire. As such, this function should perform all analysis required for a
# repertoire.
function run_analysis()
# Parameters:
#     $1 output directory
#     $2 repository name [string]
#     $3 repertoire_id ("NULL" if should skip repertoire processing)
#     $4 repertoire file (Not used if repertoire_id == NULL)
#     $5 manifest file
{
    # Use local variables - no scope issues please...
    local output_directory=$1
    local repository_name=$2
    local repertoire_id=$3
    local repertoire_file=$4
    local manifest_file=$5
    echo "IR-INFO: Running a Repertoire Analysis with manifest ${manifest_file}"
    echo "IR-INFO:     Working directory = ${output_directory}"
    echo "IR-INFO:     Repository name = ${repository_name}"
    echo "IR-INFO:     Repertoire id = ${repertoire_id}"
    echo "IR-INFO:     Repertoire file = ${repertoire_file}"
    echo "IR-INFO:     Manifest file = ${manifest_file}"
    echo -n "IR-INFO:     Current diretory = "
    pwd

    # Get a list of rearrangement files to process from the manifest.
    local array_of_files=( `python3 ${SCRIPT_DIR}/${GATEWAY_UTIL_DIR}/manifest_summary.py ${manifest_file} "rearrangement_file"` )
    if [ $? -ne 0 ]
    then
        echo "IR-ERROR: Could not process manifest file ${manifest_file}"
        return
    fi
    echo "IR-INFO:     Using files ${array_of_files[@]}"


    # Check to see if we are processing a specific repertoire_id
    if [ "${repertoire_id}" != "Total" ]; then
        # Set the R program if we are doing a repertoire by repertoire analysis.
        r_program='immunarch.R'
        file_string=`python3 ${SCRIPT_DIR}/${GATEWAY_UTIL_DIR}/repertoire_summary.py ${repertoire_file} ${repertoire_id} --separator "_"`
        file_string=${repository_name}_${file_string// /}
        title_string="$(python3 ${SCRIPT_DIR}/${GATEWAY_UTIL_DIR}/repertoire_summary.py ${repertoire_file} ${repertoire_id})"
        # TODO: Fix this, it should not be required.
        title_string=${title_string// /}
    else
        # Set the R program if we are doing a comparative analysis.
        r_program='immunarch_group.R'
        file_string="Total"
        title_string="Total"
    fi
    
    for filename in "${array_of_files[@]}"; do
        echo "IR-INFO: Running ImmunArch on $filename"
	    echo "IR-INFO: Asking for ${AGAVE_JOB_PROCESSORS_PER_NODE} threads"
	    echo "IR-INFO: Mapping ${PWD} to /data"
        echo "IR-INFO: Input data = /data/${output_directory}/data"
	    echo "IR-INFO: Storing output in /data/${output_directory}"

        # Immunarch is very permissive, it tries to process everything in the directory.
        # We want it only to process the data files, so we create a temporary directory
        # for this so immunarch doesn't try and do other weird things like analyze images
        mkdir ${PWD}/${output_directory}/data
        mv ${PWD}/${output_directory}/${filename} ${PWD}/${output_directory}/data/${filename}

	    # Run ImmunArch
        singularity exec -e -B ${PWD}:/data -B ${SCRIPT_DIR}:/localsrc ${SCRIPT_DIR}/${singularity_image} Rscript /localsrc/${r_program} /data/${output_directory}/data /data/${output_directory}
        if [ $? -ne 0 ]
        then
            echo "IR-ERROR: Immunarch failed on file ${output_directory}"
            return
        fi

	    # Remove the repertoire TSV file, we don't want to keep it around as part of the analysis results.
	    #rm -f ${PWD}/${output_directory}/data/${filename}

        # Remove the generated manifest file.
	    rm -f ${manifest_file}

        # Generate a label file for the Gateway to use to present this info to the user
        label_file=${output_directory}/${repertoire_id}.txt
        echo "${title_string}" > ${label_file}

        # Generate a summary output report for the analysis for the
        # gateway to use as a summary.
        html_file=${output_directory}/${repertoire_id}.html

        # Generate the HTML main block
        printf '<!DOCTYPE HTML5>\n' > ${html_file}
        printf '<html lang="en" dir="ltr">' >> ${html_file}

        # Generate a normal looking iReceptor header
        printf '<head>\n' >>  ${html_file}
        cat ${output_directory}/assets/head-template.html >> ${html_file}
        printf "<title>Immunarch: %s</title>\n" ${title_string} >> ${html_file}
        printf '</head>\n' >>  ${html_file}

        # Generate an iReceptor top bar for the page
        cat ${output_directory}/assets/top-bar-template.html >> ${html_file}

        # Generate a normal looking iReceptor header
        printf '<div class="container job_container">'  >> ${html_file}

        # Generate the output from the analysis.
        printf "<h2>Immunarch: %s</h1>\n" ${title_string} >> ${html_file}
        printf "<h2>Data Summary</h2>\n" >> ${html_file}
        cat info.txt >> ${html_file}
        printf "<h2>Analysis</h2>\n" >> ${html_file}
        printf "<h3>Top Clones</h3>\n" >> ${html_file}
        printf '<iframe src="%s" width="800" height="300" style="border: none;" seamless></iframe>\n' top_10_clones.html >> ${html_file}
        # The below would be more elegant but it is HTML5 and doesn't work
        #printf "<h3>Top Clones 2</h3>\n" >> ${html_file}
        #printf '<link href="%s" rel="import" />\n' top_10_clones.html >> ${html_file}
        printf '<img src="%s" width="800">\n' clonal_homeo.png >> ${html_file}
        printf '<img src="%s" width="800">\n' clonal_rare.png >> ${html_file}
        printf '<img src="%s" width="800">\n' count.png >> ${html_file}
        printf '<img src="%s" width="800">\n' gene_family_usage_normalized.png >> ${html_file}
        printf '<img src="%s" width="800">\n' gene_usage_normalized.png >> ${html_file}
        printf '<img src="%s" width="800">\n' len.png >> ${html_file}

        # End of main div container
        printf '</div>' >> ${html_file}

        # Use the normal iReceptor footer.
        cat ${output_directory}/assets/footer.html >> ${html_file}

        # Generate end body end HTML
        printf '</body>' >> ${html_file}
        printf '</html>' >> ${html_file}

    done
    printf "Done Repertoire Analysis on ${array_of_files[@]} at $(date)\n\n"
}

if [ "${split_repertoire}" = "True" ]; then
    echo -e "IR-INFO:\nIR-INFO: Splitting data by Repertoire"
    echo "IR-INFO:"
    # Split the data by repertoire. This creates a directory tree in $GATEWAY_ANALYSIS_DIR
    # with a directory per repository and within that a directory per repertoire in
    # that repository. In each repertoire directory there will exist an AIRR TSV
    # file with the data from that repertoire.
    #
    # This gateway utility function uses a callback mechanism, calling the
    # function run_analysis() on each repertoire. The run_analysis function takes
    # as paramenters the TSV files to process, the directory for the repertoire in
    # which to store the analysis results, the a string repersenting the repository
    # from which the data came, the repertoire_id, and a repertoire JSON file in which
    # information about the repertoire can be found. 
    #
    # run_analysis() is defined above.
    gateway_split_repertoire ${INFO_FILE} ${MANIFEST_FILE} ${ZIP_FILE} ${GATEWAY_ANALYSIS_DIR}
elif [ "${split_repertoire}" = "False" ]; then
    echo -e "IR-INFO:\nIR-INFO: Running app on entire data set"
    echo "IR-INFO:"

    # Output directory is called "Total"
    # Run the analysis with a token repository name of "ADC" since the
    # analysis is being run on data from the entire ADC.
    # repertoire_id and repository should be "NULL"
    # Lastly, provide the list of TSV files to process. Remember that
    # the array elements are expanded into separate parameters, which
    # the run_analyis function handles.
    outdir="Total"

    # Run the stats on all the data combined. Unzip the files
    gateway_unzip ${ZIP_FILE} ${GATEWAY_ANALYSIS_DIR}/${outdir}

    # Run the stats analysis.
    run_analysis ${GATEWAY_ANALYSIS_DIR}/${outdir} "AIRRDataCommons" ${outdir} "NULL" ${GATEWAY_ANALYSIS_DIR}/${outdir}/${MANIFEST_FILE}

else
    echo "IR-ERROR: Unknown repertoire operation ${split_repertoire}" >&2
    exit 1
fi


#gateway_split_repertoire ${INFO_FILE} ${MANIFEST_FILE} ${ZIP_FILE} ${GATEWAY_ANALYSIS_DIR}

# Make sure we are back where we started, although the gateway functions should
# not change the working directory that we are in.
cd ${SCRIPT_DIR}

# We want to move the info.txt to the main directory. The Gateway expects this.
cp ${GATEWAY_ANALYSIS_DIR}/${INFO_FILE} .

# We want the job error and output files to be part of the analysis so copy them
cp *.err ${GATEWAY_ANALYSIS_DIR}
cp *.out ${GATEWAY_ANALYSIS_DIR}

# Zip up the analysis results for easy download
echo "ZIPing analysis results"
zip -r ${GATEWAY_ANALYSIS_DIR}.zip ${GATEWAY_ANALYSIS_DIR}

# We don't want the analysis files to remain - they are in the ZIP file
echo "Removing analysis output"
rm -rf ${GATEWAY_ANALYSIS_DIR}

# We don't want to copy around the singularity image everywhere.
rm -f ${singularity_image}

# We don't want the iReceptor Utilities to be part of the results.
rm -rf ${GATEWAY_UTIL_DIR}

# Cleanup the input data files, don't want to return them as part of the resulting analysis
echo "Removing original ZIP file $ZIP_FILE"
rm -f $ZIP_FILE

# End
printf "DONE at $(date)\n\n"

# Handle AGAVE errors
#printf "AGAVE callback error = ${AGAVE_JOB_CALLBACK_FAILURE} \n\n"
#if [[ $JOB_ERROR -eq 1 ]]; then
#    ${AGAVE_JOB_CALLBACK_FAILURE}
#fi
