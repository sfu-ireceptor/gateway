# Wrapper script for running app through the iReceptor Gateway.
#

echo "IR-INFO: iReceptor OLGA App"

# Get the script directory where all the code is.
SCRIPT_DIR=${_tapisExecSystemExecDir}
echo "IR-INFO: Running job from ${SCRIPT_DIR}"

########################################################################
# Tapis configuration/settings
########################################################################

#
# Tapis ENV variables expected
#
# Get the ZIP_FILE
ZIP_FILE=${IR_DOWNLOAD_FILE}

#
# Tapis App Parameters: Will be subsituted by Tapis on the command line to the singularity
# command that is executed in the order specified in the App JSON file
#
# No arguements, always split repertoire
SPLIT_REPERTOIRE=True

##############################################
# Set up Gateway Utilities
##############################################
echo "IR-INFO: Using Gateway ${IR_GATEWAY_URL}"

# Report where we get the Gateway utilities from
GATEWAY_UTIL_DIR=${IR_GATEWAY_UTIL_DIR}
echo "IR-INFO: Using iReceptor Gateway Utilities from ${IR_GATEWAY_UTIL_DIR}"

# Load the iReceptor Gateway utilities functions.
source ${IR_GATEWAY_UTIL_DIR}/gateway_utilities.sh
if [ $? -ne 0 ]
then
    echo "IR-ERROR: Could not load GATEWAY UTILIIES"
    exit 1
fi

# This directory is defined in the gateway_utilities.sh. The Gateway
# relies on this being set. If it isn't set, abort as something has
# gone wrong with loading the Gateway utilties.
echo "IR-INFO: Gateway analysis directory = ${GATEWAY_ANALYSIS_DIR}"
if [ -z "${GATEWAY_ANALYSIS_DIR}" ]; then
        echo "IR-ERROR: GATEWAY_ANALYSIS_DIR not defined, gateway_utilities not loaded correctly." >&2
    exit 1
fi
echo "IR-INFO: Done loading iReceptor Gateway Utilities"

#########################################################################
# Application variables (will be subsituted by Tapis). If they don't exist
# use command line arguments.
#########################################################################

# Start
printf "IR-INFO: \nIR-INFO: \n"
printf "IR-INFO: START at $(date)\n"
printf "IR-INFO: PROCS = ${_tapisCoresPerNode}\n"
printf "IR-INFO: MEM = ${_tapisMemoryMB}\n"
printf "IR-INFO: MAX RUNTIME = ${_tapisMaxMinutes}\n"
printf "IR-INFO: SLURM JOB ID = ${SLURM_JOB_ID}\n"
printf "IR-INFO: ZIP FILE = ${ZIP_FILE}\n"
printf "IR-INFO: SPLIT_REPERTOIRE = ${SPLIT_REPERTOIRE}\n"
printf "IR-INFO: IR_GATEWAY_JOBID = ${IR_GATEWAY_JOBID}\n"
printf "IR-INFO: "
lscpu | grep "Model name"
printf "IR-INFO: \nIR-INFO: \n"

echo "IR-INFO: Running histogram on variable ${VARNAME}"

#########################################################################
# Code to do the analysis
#########################################################################

function run_olga()
# Parameters:
#     $1 output directory
#     $2 name of processing object (use to tag file)
#     $3 title of processing object (use in title of graph)
#     $4-$N remaining arguments are files to process.
{
    # Get the local variables to use
    local output_dir=$1
    local file_tag=$2
    local title=$3
    shift
    shift
    shift
    # Remaining variable are the files to process
    local array_of_files=( $@ )

    # Use a temporary file for output
    TMP_FILE=${output_dir}/tmp.tsv
    rm -f $TMP_FILE
    touch $TMP_FILE

    # preprocess input files -> tmp.csv
    echo "IR-INFO: "
    echo "IR-INFO:     Olga started at: `date`"

    # Set a default repertoire locus
    germline_set="humanTRB"

    # Loop over each file
    for file in "${array_of_files[@]}"; do
        data_file=${output_dir}/${file}
        echo "IR-INFO:         Processing ${data_file}"
        # Get the columns required by olga
        junction_column=$(head -n 1 ${data_file} | awk -F"\t" -v label=junction '{for(i=1;i<=NF;i++){if ($i == label){print i}}}')
        junction_aa_column=$(head -n 1 ${data_file} | awk -F"\t" -v label=junction_aa '{for(i=1;i<=NF;i++){if ($i == label){print i}}}')
        v_call_column=$(head -n 1 ${data_file} | awk -F"\t" -v label=v_call '{for(i=1;i<=NF;i++){if ($i == label){print i}}}')
        j_call_column=$(head -n 1 ${data_file} | awk -F"\t" -v label=j_call '{for(i=1;i<=NF;i++){if ($i == label){print i}}}')
        # Check to make sure we found them, and if not, print an error message and skip this file.
        if [[ -z "$junction_column" ]]; then
            echo "IR-ERROR: Could not find required column junction in ${data_file}"
            continue
        fi
        if [[ -z "$junction_aa_column" ]]; then
            echo "IR-ERROR: Could not find required column junction_aa in ${data_file}"
            continue
        fi
        if [[ -z "$v_call_column" ]]; then
            echo "IR-ERROR: Could not find required column v_call in ${data_file}"
            continue
        fi
        if [[ -z "$j_call_column" ]]; then
            echo "IR-ERROR: Could not find required column j_call in ${data_file}"
            continue
        fi
    
        # Check the rearrangement file and extract the list
        # of loci in the data 
        repertoire_locus=( `cat $data_file | cut -f ${v_call_column} | tail --lines=+2 | awk '{printf("%s\n", substr($1,0,3))}' | sort -u | awk '{printf("%s  ",$0)}'` )
        if [ $? -ne 0 ]
        then
            echo "IR-ERROR: Could not get a locus for repertoire ${repertoire_id}"
            echo "IR-ERROR: Processing for repertoire ${repertoire_id} (${title}) not completed."
            continue
        fi

        # Check to see if there is only one locus in the data.
        if [ ${#repertoire_locus[@]} != 1 ]
        then
            echo "IR-ERROR: Olga analysis requires a single locus (repertoire_id = ${repertoire_id}, loci = ${repertoire_locus[@]})."
            echo "IR-ERROR: Processing for repertoire ${repertoire_id} (${title}) not completed."
            continue
        fi

        # If there is only one, check to see if it is one we have a germline
        # for, if so then we are good, if not it is an error.
        repertoire_locus=${repertoire_locus[0]}

        if [ "${repertoire_locus}" == "TRB" ]
        then
            germline_set="humanTRB"
        elif [ "${repertoire_locus}" == "TRA" ]
        then
            germline_set="humanTRA"
        elif [ "${repertoire_locus}" == "IGH" ]
        then
            germline_set="humanIGH"
        elif [ "${repertoire_locus}" == "IGK" ]
        then
            germline_set="humanIGK"
        elif [ "${repertoire_locus}" == "IGL" ]
        then
            germline_set="humanIGL"
        else
            echo "IR-ERROR: Olga analysis can only run on TRA, TRB, IGH, IGH, or IGL repertoires (repertoire_id = ${repertoire_id}, locus type = ${repertoire_locus})."
            echo "IR-ERROR: Processing for repertoire ${repertoire_id} (${title}) not completed."
            continue
        fi

        tail -n +2 ${data_file} | awk -F"\t" -v junction_column=${junction_column} -v junction_aa_column=${junction_aa_column} -v v_call_column=${v_call_column} -v j_call_column=${j_call_column} '{printf("%s\t%s\t%s\t%s\n",$junction_column,$junction_aa_column,"",$v_call_column,$j_call_column)}' >> $TMP_FILE

    done

    # Generate the image file name.
    OFILE_BASE="${file_tag}"
    PGEN_OFILE=${output_dir}/${OFILE_BASE}-pgen.tsv
    PGEN_TMP_FILE=${output_dir}/${OFILE_BASE}-tmp-pgen.tsv

    # Debugging output
    echo "IR-INFO:             Input file = $TMP_FILE"
    echo "IR-INFO:             Output file = $PGEN_OFILE"
    echo "IR-INFO:             Germline set = $germline_set"

    # Run the python histogram command
    echo -e "Junction NT sequence\tJunction NT PGEN\tJunction AA sequence\tJunction AA PGEN" > ${PGEN_OFILE}
    olga-compute_pgen --display_off --time_updates_off --${germline_set} -i ${TMP_FILE} -o ${PGEN_TMP_FILE} >&2
    if [ $? -ne 0 ]
    then
        echo "IR-ERROR: Could not generate Olga PGEN for ${title}"
    fi
    cat ${PGEN_TMP_FILE} >> ${PGEN_OFILE}
    rm ${PGEN_TMP_FILE}

    # change permissions
    chmod 644 $PGEN_OFILE

    # Remove the temporary file.
    rm -f $TMP_FILE
    echo "IR-INFO:     Olga finished at: `date`"
    echo "IR-INFO: "
}

function run_analysis()
# Parameters:
#     $1 output directory
#     $2 repository name [string]
#     $3 repertoire_id [string] "Total" if aggergate/combined analysis
#     $4 repertoire file (Not used if repertoire_id == "Total")
#     $5 manifest file
#
# Note: this function assumes that the jobs are running from the base
# analysis directory, with files and directories (e.g. $1, $5) being specified
# relative to that location.
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
    array_of_files=( `python3 ${IR_GATEWAY_UTIL_DIR}/manifest_summary.py ${manifest_file} "rearrangement_file"` )
    if [ $? -ne 0 ]
    then
        echo "IR-ERROR: Could not process manifest file ${manifest_file}"
        return
    fi
    echo "IR-INFO:     Using files ${array_of_files[@]}"

    # Keep track of a base file string, use repertoire_id
    file_string=${repertoire_id}
    # Check to see if we are processing a specific repertoire_id
    # If so, generate an appropriate title
    if [ "${repertoire_id}" != "Total" ]; then
        title_string="$(python3 ${IR_GATEWAY_UTIL_DIR}/repertoire_summary.py ${repertoire_file} ${repertoire_id})"
        if [ $? -ne 0 ]
        then
            echo "IR-ERROR: Could not generate repertoire summary from ${repertoire_file}"
            return
        fi
    else
        title_string="Total"
    fi
    # Clean up special characters in file and title strings.
    file_string=$(echo ${repository_name}_${file_string} | tr -dc "[:alnum:]._-")

    # TODO: Fix this, it should not be required.
    title_string=`echo ${title_string} | sed "s/[ ]//g"`

    # Generate the histogram
    run_olga $output_directory $file_string $title_string ${array_of_files[@]}

    # Remove the TSV files, we don't want to return them
    for filename in "${array_of_files[@]}"; do
        echo "IR_INFO:     Removing file $output_directory/$filename"
        rm -f $output_directory/$filename
    done

    # Generate a label file for the Gateway to use to present this info to the user
    label_file=${output_directory}/${repertoire_id}.txt
    echo "${title_string}" > ${label_file}

    # Generate a summary HTML file for the Gateway to present this info to the user
    html_file=${output_directory}/${repertoire_id}.html

    # Generate the HTML main block
    printf '<!DOCTYPE HTML5>\n' > ${html_file}
    printf '<html lang="en" dir="ltr">' >> ${html_file}

    # Generate a normal looking iReceptor header
    printf '<head>\n' >>  ${html_file}
    cat ${output_directory}/assets/head-template.html >> ${html_file}
    printf "<title>Olga: %s</title>\n" ${title_string} >> ${html_file}
    printf '</head>\n' >>  ${html_file}

    # Generate an iReceptor top bar for the page
    cat ${output_directory}/assets/top-bar-template.html >> ${html_file}

    # Generate a normal looking iReceptor header
    printf '<div class="container job_container">'  >> ${html_file}

    printf "<h2>Olga: %s</h2>\n" ${title_string} >> ${html_file}
    # Use the tcrmatch table generator to generate a nice table for the first N values.
    printf "<h2>Analysis</h2>\n" >> ${html_file}
    NUM_VALUES=100
    printf "<p>First %d generation probabilities for junction NT and AA sequences. For all generation probabilties please refer to the PGEN TSV file in the 'Analysis Details' output</p>\n" $NUM_VALUES >> ${html_file}
    pgen_subset_tmp=$(mktemp)
    head -${NUM_VALUES} ${output_directory}/${file_string}-pgen.tsv > $pgen_subset_tmp
    python3 ${IR_GATEWAY_UTIL_DIR}/tcrmatch-to-html.py --max_width=50 $pgen_subset_tmp >> ${html_file}
    rm $pgen_subset_tmp

    # End of main div container
    printf '</div>' >> ${html_file}

    # Use the normal iReceptor footer.
    cat ${output_directory}/assets/footer.html >> ${html_file}

    # Generate end body end HTML
    printf '</body>' >> ${html_file}
    printf '</html>' >> ${html_file}

    # Generate a summary HTML file for the Gateway to present this info to the user
    html_file=${output_directory}/${repertoire_id}-gateway.html
    printf "<h2>Olga: %s</h2>\n" ${title_string} >> ${html_file}
    # Use the tcrmatch table generator to generate a nice table for the first N values.
    printf "<h2>Analysis</h2>\n" >> ${html_file}
    NUM_VALUES=100
    printf "<p>First %d generation probabilities for junction NT and AA sequences. For all generation probabilties please refer to the PGEN TSV file in the 'Analysis Details' output\n" $NUM_VALUES >> ${html_file}
    pgen_subset_tmp=$(mktemp)
    head -${NUM_VALUES} ${output_directory}/${file_string}-pgen.tsv > $pgen_subset_tmp
    python3 ${IR_GATEWAY_UTIL_DIR}/tcrmatch-to-html.py --max_width=50 $pgen_subset_tmp >> ${html_file}
    rm $pgen_subset_tmp

}

# Set up the required variables. An iReceptor Gateway download consists
# of both an "info.txt" file that describes the download as well as an
# AIRR manifest JSON file that describes the relationships between
# AIRR Repertoire JSON files and AIRR TSV files.
INFO_FILE="info.txt"
AIRR_MANIFEST_FILE="AIRR-manifest.json"

if [ "${SPLIT_REPERTOIRE}" = "True" ]; then
    echo -e "IR-INFO: \nIR-INFO: Splitting data by Repertoire\n"
    # Split the download into single repertoire files, with a directory per
    # repository and within that a directory per repertoire. This expects the
    # user to define a function called run_analysis() that will be
    # called for each repertoire. See the docs in the gateway_utilities.sh file
    # for parameters to this function.
    gateway_split_repertoire ${INFO_FILE} ${AIRR_MANIFEST_FILE} ${ZIP_FILE} ${GATEWAY_ANALYSIS_DIR}
    gateway_run_analysis ${INFO_FILE} ${AIRR_MANIFEST_FILE} ${GATEWAY_ANALYSIS_DIR}
    gateway_cleanup ${ZIP_FILE} ${AIRR_MANIFEST_FILE} ${GATEWAY_ANALYSIS_DIR}
    gateway_summary


elif [ "${SPLIT_REPERTOIRE}" = "False" ]; then
    echo -e "IR-INFO: \nIR-INFO: Running app on entire data set\n"
    # Run the analysis with a token repository name of "ADC" since the
    # analysis is being run on data from the entire ADC.
    # repertoire_id is "Total" since it isn't a repertoire analysis. 
    repertoire_id="Total"
    repository="AIRRDataCommons"
    outdir=${repository}/${repertoire_id}


    # Unzip the files in the base directory like a normal analysis
    gateway_unzip ${ZIP_FILE} ${GATEWAY_ANALYSIS_DIR}
    # Also unzip into the analysis dir, as the files in the zip
    # are the files to perform the analysis on.
    gateway_unzip ${ZIP_FILE} ${GATEWAY_ANALYSIS_DIR}/${outdir}

    # Copy the HTML resources for the Apps
    echo "IR-INFO: Copying HTML assets"
    mkdir -p ${GATEWAY_ANALYSIS_DIR}/${outdir}/assets
    cp -r ${IR_GATEWAY_UTIL_DIR}/assets/* ${GATEWAY_ANALYSIS_DIR}/${outdir}/assets
    if [ $? -ne 0 ]
    then
        echo "IR-ERROR: Could not create HTML asset directory"
    fi

    # Run the analysis. We need to run this from the GATEWAY_ANALYSIS_DIR
    cd ${GATEWAY_ANALYSIS_DIR}
    run_analysis ${outdir} ${repository} ${repertoire_id} "NULL" ${outdir}/${AIRR_MANIFEST_FILE}

    # Clean up after doing the analysis. We don't want to leave behind all of the
    # large TSV and zip files etc.
    gateway_cleanup ${ZIP_FILE} ${AIRR_MANIFEST_FILE} ${GATEWAY_ANALYSIS_DIR}
    gateway_summary
else
    echo "IR-ERROR: Unknown repertoire operation ${SPLIT_REPERTOIRE}" >&2
    exit 1
fi

# Make sure we are back where we started, although the gateway functions should
# not change the working directory that we are in.
cd ${SCRIPT_DIR}

# We want to move the info.txt to the main directory as the Gateway uses it if
# it is available.
cp ${GATEWAY_ANALYSIS_DIR}/${INFO_FILE} .

# We want to keep the job error and output files as part of the analysis output.
cp *.err ${GATEWAY_ANALYSIS_DIR}
cp *.out ${GATEWAY_ANALYSIS_DIR}

# ZIP up the analysis results for easy download
zip -r ${GATEWAY_ANALYSIS_DIR}.zip ${GATEWAY_ANALYSIS_DIR}
mv ${GATEWAY_ANALYSIS_DIR}.zip output/

# We don't want the analysis files to remain - they are in the ZIP file
echo "IR-INFO: Removing analysis output"
rm -rf ${GATEWAY_ANALYSIS_DIR}

# Cleanup the input data files, don't want to return them as part of the resulting analysis
echo "IR-INFO: Removing original ZIP file $ZIP_FILE"
rm -f $ZIP_FILE

# Debugging output, print data/time when shell command is finished.
echo "IR-INFO: Olga finished at: `date`"

