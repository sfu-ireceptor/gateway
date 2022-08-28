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

# If you want to tell Tapis that the job failed
export JOB_ERROR=1

########################################################################
# Done Tapis setup/processing.
########################################################################

GATEWAY_URL="https://gateway-analysis-dev.ireceptor.org"
echo "IR-INFO: Using Gateway ${GATEWAY_URL}"

# Get the singularity image from the Gateway
echo "Downloading singularity image ${singularity_image} from the Gateway"
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
if [ $? -ne 0 ]
then
    echo "IR-ERROR: Could not load GATEWAY UTILIIES"
    exit $?
fi

# This directory is defined in the gateway_utilities.sh. The Gateway
# relies on this being set. If it isn't set, abort as something has
# gone wrong with loading the Gateway utilties.
echo "Gateway analysis directory = ${GATEWAY_ANALYSIS_DIR}"
if [ -z "${GATEWAY_ANALYSIS_DIR}" ]; then
        echo "IR-ERROR: GATEWAY_ANALYSIS_DIR not defined, gateway_utilities not loaded correctly."
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
printf "\n\n"
printf "START at $(date)\n\n"
printf "PROCS = ${AGAVE_JOB_PROCESSORS_PER_NODE}\n\n"
printf "MEM = ${AGAVE_JOB_MEMORY_PER_NODE}\n\n"
printf "SLURM JOB ID = ${SLURM_JOB_ID}\n\n"
printf "\n\n"

# This function is called by the iReceptor Gateway utilities function gateway_split_repertoire
# The gateway utility function splits all data into repertoires and then calls this function
# for a single repertoire. As such, this function should perform all analysis required for a
# repertoire.
function run_cell_analysis()
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
    echo "Running a Cell Repertoire Analysis with manifest ${manifest_file}"

    # Get a list of rearrangement files to process from the manifest.
    local cell_files=( `python3 ${SCRIPT_DIR}/${GATEWAY_UTIL_DIR}/manifest_summary.py ${manifest_file} "cell_file"` )
    if [ $? -ne 0 ]
    then
        echo "IR-ERROR: Could not process manifest file ${manifest_file}"
        return
    fi
    if [ ${#cell_files[@]} != 1 ]
    then
        echo "IR_ERROR: Conga cell analysis only works with a single cell file."
        return
    fi
    local cell_file_count=${#cell_files[@]}
    local cell_file=${cell_files[0]}
    echo "    Using cell file ${cell_file}"
    local gex_files=( `python3 ${SCRIPT_DIR}/${GATEWAY_UTIL_DIR}/manifest_summary.py ${manifest_file} "expression_file"` )
    if [ ${#gex_files[@]} != 1 ]
    then
        echo "IR_ERROR: Conga cell analysis only works with a single expression file."
        return
    fi
    local gex_file=${gex_files[0]}
    echo "    Using gex file ${gex_file}"
    local rearrangement_files=( `python3 ${SCRIPT_DIR}/${GATEWAY_UTIL_DIR}/manifest_summary.py ${manifest_file} "rearrangement_file"` )
    if [ ${#rearrangement_files[@]} != 1 ]
    then
        echo "IR_ERROR: Conga cell analysis only works with a single rearrangement file."
        return
    fi
    local rearrangement_file=${rearrangement_files[0]}
    echo "    Using rearrangement file ${rearrangement_files}"

    # Check to see if we are processing a specific repertoire_id
    if [ "${repertoire_id}" != "NULL" ]; then
        file_string=`python3 ${SCRIPT_DIR}/${GATEWAY_UTIL_DIR}/repertoire_summary.py ${repertoire_file} ${repertoire_id} --separator "_"`
        file_string=${repository_name}_${file_string// /}
        title_string="$(python3 ${SCRIPT_DIR}/${GATEWAY_UTIL_DIR}/repertoire_summary.py ${repertoire_file} ${repertoire_id})"
        # TODO: Fix this, it should not be required.
        title_string=${title_string// /}
    else
        file_string="total"
        title_string="Total"
    fi

    # Run the Conga pipeline within the singularity image on each rearrangement file provided.
    echo "Running Conga on $cell_file"
    echo "Mapping ${PWD} to /data"
    echo "Asking for ${AGAVE_JOB_PROCESSORS_PER_NODE} threads"
    echo "Storing output in /data/${output_directory}"

    # Generate equivalent 10X Cell files from AIRR Cell/GEX data for input into Conga.
    python3 ${SCRIPT_DIR}/airr-to-10x.py ${output_directory}/${cell_file} ${output_directory}/${gex_file} ${output_directory}/features.tsv ${output_directory}/barcodes.tsv ${output_directory}/matrix.mtx
    if [ $? -ne 0 ]
    then
        echo "IR-ERROR: Could not generate cell/expression data"
        return
    fi
        
    # Compress the file because Conga wants it that way!
    gzip ${output_directory}/features.tsv ${output_directory}/barcodes.tsv ${output_directory}/matrix.mtx

    # Convert Rearrangement file to a 10X Contig file
    CONTIG_PREFIX=10x-contig
    python3 ${SCRIPT_DIR}/rearrangements-to-10x.py ${output_directory}/${rearrangement_file} ${output_directory}/${CONTIG_PREFIX}.csv
    if [ $? -ne 0 ]
    then
        echo "IR-ERROR: Could not generate rearrangements"
        return
    fi

    # Run Conga
    singularity exec --cleanenv --env PYTHONNOUSERSITE=1 -B ${PWD}:/data ${SCRIPT_DIR}/${singularity_image} python3 /gitrepos/conga/scripts/setup_10x_for_conga.py --filtered_contig_annotations_csvfile /data/${output_directory}/${CONTIG_PREFIX}.csv --organism human

    singularity exec --cleanenv --env PYTHONNOUSERSITE=1 -B ${PWD}:/data ${SCRIPT_DIR}/${singularity_image} python3 /gitrepos/conga/scripts/run_conga.py --all --organism human --clones_file /data/${output_directory}/${CONTIG_PREFIX}_tcrdist_clones.tsv --gex_data /data/${output_directory} --gex_data_type 10x_mtx --outfile_prefix /data/${output_directory}/${file_string}


    # Generate a summary HTML file for the Gateway to present this info to the user
    html_file=${output_directory}/${repertoire_id}.html

    # Generate the HTML main block
    printf '<!DOCTYPE HTML5>\n' > ${html_file}
    printf '<html lang="en" dir="ltr">' >> ${html_file}

    # Generate a normal looking iReceptor header
    printf '<head>\n' >>  ${html_file}
    cat ${output_directory}/assets/head-template.html >> ${html_file}
    printf "<title>Conga: %s</title>\n" ${title_string} >> ${html_file}
    printf '</head>\n' >>  ${html_file}

    # Generate an iReceptor top bar for the page
    cat ${output_directory}/assets/top-bar-template.html >> ${html_file}

    # Generate a normal looking iReceptor header
    printf '<div class="container job_container">'  >> ${html_file}
    printf "<h2>Conga: %s</h2>\n" ${title_string} >> ${html_file}

    printf "<h2>Analysis</h2>\n" >> ${html_file}

    printf "<h3>Conga Analysis: %s</h3>\n" ${title_string} >> ${html_file}
    printf '<iframe src="%s" width="100%%", height="700px"></iframe>\n' ${file_string}_results_summary.html >> ${html_file}

    # End of main div container
    printf '</div>' >> ${html_file}

    # Use the normal iReceptor footer.
    cat ${output_directory}/assets/footer.html >> ${html_file}

    # Generate end body end HTML
    printf '</body>' >> ${html_file}
    printf '</html>' >> ${html_file}


    # Copy the Conga summary report to the gateway expected summary for this repertoire
    #cp ${output_directory}/${file_string}_results_summary.html ${output_directory}/${repertoire_id}.html
    # Add the required label file for the Gateway to present the results as a summary.
    label_file=${output_directory}/${repertoire_id}.txt
    echo "${title_string}" > ${label_file}

    # Remove the intermediate files generated for Conga
    rm -f ${output_directory}/${CONTIG_PREFIX}.csv ${output_directory}/${CONTIG_PREFIX}_*
    rm -f ${output_directory}/features.tsv.gz ${output_directory}/barcodes.tsv.gz ${output_directory}/matrix.mtx.gz ${output_directory}/matrix.mtx.tmp

    # We don't want to keep around the generated data files or the manifest file.
    rm -f ${output_directory}/${cell_file} ${output_directory}/${gex_file} ${output_directory}/${rearrangement_file} ${output_directory}/${manifest_file}

    # done
    printf "Done running Repertoire Analysis on ${cell_file} at $(date)\n\n"
}

# Split the data by repertoire. This creates a directory tree in $GATEWAY_ANALYSIS_DIR
# with a directory per repository and within that a directory per repertoire in
# that repository. In each repertoire directory there will exist an AIRR manifest
# file and the data (as described in the manifest file) from that repertoire.
#
# This gateway utility function uses a callback mechanism, calling the
# function run_cell_analysis() on each repertoire (in this case run_cell_analysis 
# because the type is "cell_file". The run_cell_analysis function takes
# as paramenters the manifest files to process, the directory for the repertoire in
# which to store the analysis results, the a string repersenting the repository
# from which the data came, the repertoire_id, and a repertoire JSON file in which
# information about the repertoire can be found. 
#
# run_cell_analysis() is defined above.
gateway_split_repertoire ${INFO_FILE} ${MANIFEST_FILE} ${ZIP_FILE} ${GATEWAY_ANALYSIS_DIR} "cell_file"

# Make sure we are back where we started, although the gateway functions should
# not change the working directory that we are in.
cd ${SCRIPT_DIR}

# We want to move the info.txt file to the main directory.
cp ${GATEWAY_ANALYSIS_DIR}/${INFO_FILE} .

# We want to keep the job error and output files as part of the analysis output.
cp *.err ${GATEWAY_ANALYSIS_DIR}
cp *.out ${GATEWAY_ANALYSIS_DIR}

# Zip up the analysis results for easy download
echo "ZIPing analysis results - $(date)"
zip -r ${GATEWAY_ANALYSIS_DIR}.zip ${GATEWAY_ANALYSIS_DIR}
echo "Done ZIPing analysis results - $(date)"

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

