#!/bin/bash

#
# Wrapper script for running app through the iReceptor Gateway.
#

echo "IR-INFO: iReceptor Junction AA Search - starting at: `date`"

# Unset any locally set SSL Cert bundles. If such an environment
# variable is set it will point to something that doesn't exist
# inside the container.
unset SSL_CERT_FILE
unset CURL_CA_BUNDLE

# Get the script directory where all the code is.
IR_JOB_DIR=${_tapisExecSystemExecDir}
echo "IR-INFO: Running job from ${IR_JOB_DIR}"

#
# Tapis App Parameters: Will be on the singularity command line to
# the App in the order specified in the App JSON file.
#
# SPLIT_REPERTOIRE flag (True or False)
SPLIT_REPERTOIRE="${1}"

# SPLIT_JUNCTION flag (True or False)
SPLIT_JUNCTION="${2}"

# Next parameter is the comma separated list of Juncion AA sequences
JUNCTION_AA_LIST="${3}"

# Environment variable IR_GATEWAY_URL contains the URL of the source gateway. Use
# this to gather iReceptor Gateway specific resources if needed.
#
# Download file to be used is stored in IR_DOWNLOAD_FILE
#
# Tapis parameter IR_GATEWAY_URL contains the URL of the source gateway. Use
# this to gather iReceptor Gateway specific resources if needed.
echo "IR-INFO: Using Gateway ${IR_GATEWAY_URL}"
echo "IR-INFO: Download ZIP file = ${IR_DOWNLOAD_FILE}"

##############################################
# Set up Gateway Utilities
##############################################

# Report where we get the Gateway utilities from
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

##############################################
# Output status of app for log file
##############################################
printf "IR-INFO:\n"
printf "IR-INFO: START at $(date)\n"
printf "IR-INFO: PROCS = ${_tapisCoresPerNode}\n"
printf "IR-INFO: MEM = ${_tapisMemoryMB}\n"
printf "IR-INFO: MAX RUNTIME = ${_tapisMaxMinutes}\n"
printf "IR-INFO: SLURM JOB ID = ${SLURM_JOB_ID}\n"
printf "IR-INFO: ZIP FILE = ${IR_DOWNLOAD_FILE}\n"
printf "IR-INFO: SPLIT_REPERTOIRE = ${SPLIT_REPERTOIRE}\n"
printf "IR-INFO: SPLIT_JUNCTION = ${SPLIT_JUNCTION}\n"
printf "IR-INFO: IR_GATEWAY_JOBID = ${IR_GATEWAY_JOBID}\n"
printf "IR-INFO: "
lscpu | grep "Model name"
printf "IR-INFO: \n"

##############################################
# Analysis function that gets run on the data
# This is called by either this shell script
# or by the Gateway Utilities and requires the
# following parameters.
##############################################
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
    echo "IR-INFO: Running an Analysis with manifest ${manifest_file}"
    echo "IR-INFO:     Working directory = ${output_directory}"
    echo "IR-INFO:     Repository name = ${repository_name}"
    echo "IR-INFO:     Repertoire id = ${repertoire_id}"
    echo "IR-INFO:     Repertoire file = ${repertoire_file}"
    echo "IR-INFO:     Manifest file = ${manifest_file}"
    echo "IR-INFO:     Junction list = ${JUNCTION_AA_LIST}"
    echo -n "IR-INFO:     Current diretory = "
    pwd

    # Get a file with the list of repositories to process.
    local url_file=${output_directory}/${repertoire_id}_url.tsv
    echo "URL" > ${url_file}
    # mainfest_summary.py puts files on the same line with a space
    # separator so we need to use sed to put each on a new line.
    python3 ${IR_GATEWAY_UTIL_DIR}/manifest_summary.py ${manifest_file} "repository_url" | sed 's/ /\n/g' >> ${url_file}
    if [ $? -ne 0 ]
    then
        echo "IR-ERROR: Could not process manifest file ${manifest_file}"
        return 
    fi
	echo "IR-INFO:     Using Repositories:"
    cat ${url_file}
    echo ""

	# Check to see if we are processing a specific repertoire_id
	file_string="${repertoire_id}"
	title_string="${repertoire_id}"

    # Clean up special characters in file and title strings.
    file_string=$(echo ${repository_name}_${file_string} | tr -dc "[:alnum:]._-")

    # TODO: Fix this, it should not be required.
    title_string=`echo ${title_string} | sed "s/[ ]//g"`

    # Generate the query files. There is either one or N where N is
    # the number of junction_aa sequences provided.
    local junction_query_file=${output_directory}/${repertoire_id}_junction.json
    local fieldname="junction_aa"
    local facetname="repertoire_id"
    if [ "${SPLIT_JUNCTION}" = "True" ]; then
        # We loop over the junctions and create a query file per junction.
        IFS=',' read -ra values <<< "$JUNCTION_AA_LIST"
        for value in "${values[@]}"; do
            junction_query_file="${output_directory}/junction_query_${value}.json"
            json='{"filters": '
            json+='{"op": "=", "content": {"field": "'"$fieldname"'", "value": "'"$value"'"}}'
            json+=',"facets": "'"$facetname"'"}'
            # Print the final JSON output
            echo "$json" > ${junction_query_file}
        done
    elif [ "${SPLIT_JUNCTION}" = "False" ]; then
        # We have one junction query file.
        junction_query_file=${output_directory}/"junction_query_all.json"

        # Convert the input string into a JSON array of junction filters
        json='{"filters": {"op": "or", "content": ['

        # Loop over the string and create a clause in the or array for each junction
        first_entry=true
        IFS=',' read -ra values <<< "$JUNCTION_AA_LIST"
        for value in "${values[@]}"; do
            if [ "$first_entry" = true ]; then
                first_entry=false
            else
                json+=','
            fi
            json+='{"op": "=", "content": {"field": "'"$fieldname"'", "value": "'"$value"'"}}'
        done
    
        # Do a facet on repertoire_id to count per repertoire.
        json+=']},"facets": "'"$facetname"'"}'

        # Print the final JSON output
        echo "$json" > ${junction_query_file}
    else
        echo "IR_ERROR: Unknown junction split flag ${SPLIT_JUNCTION}"
        return
    fi
    echo "IR-INFO: Junction query = "
    cat ${junction_query_file} 
    echo "IR-INFO:"

    local output_file=${output_directory}/${repertoire_id}_output.json
    local repertoire_query_file=${output_directory}/repertoire_query.json
    local field_file=${IR_JOB_DIR}/repertoire_fields.tsv
    echo "Fields" > ${field_file}
    echo "repertoire_id" >> ${field_file}
    echo "study.study_id" >> ${field_file}
    echo "study.study_title" >> ${field_file}
    echo "subject.diagnosis.disease_diagnosis.label" >> ${field_file}
    echo "subject.subject_id" >> ${field_file}
    echo "sample.sample_id" >> ${field_file}
    echo "sample.tissue.label" >> ${field_file}
    echo "sample.collection_time_point_relative" >> ${field_file}
    echo "sample.collection_time_point_relative_unit.label" >> ${field_file}
    echo "sample.collection_time_point_reference" >> ${field_file}
    echo "sample.pcr_target.pcr_target_locus" >> ${field_file}
    echo "sample.cell_subset.label" >> ${field_file}
    echo "sample.cell_phenotype" >> ${field_file}

    python3 /ireceptor/adc-search.py ${url_file} ${repertoire_query_file} ${junction_query_file} --field_file=${field_file} --output_file=${output_file}
    if [ $? -ne 0 ]
    then
        echo "IR-ERROR: Could not complete search for Junction AA"
        return 
    fi

    # Change JSON to TSV file
    local tsv_output_file=${output_directory}/${repertoire_id}_output.tsv
    python3 /ireceptor/facet-to-tsv.py ${output_file} --output_file=$tsv_output_file

    # Generate a label file for the Gateway to use to present this info to the user
    label_file=${output_directory}/${repertoire_id}.txt
    echo "${title_string}" > ${label_file}

    # Generate a summary HTML file for the Gateway to present this info to the user
    gateway_file=${output_directory}/${repertoire_id}-gateway.html
    echo "<h2>Junction AA counts</h2>" > $gateway_file
    echo '<table style="table-layout:fixed; width=100%; border-collapse:collapse">' >> $gateway_file
    head -1 $tsv_output_file | sed -e 's/\t/<\/th><th style="width:150px;max-width:150px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;border:1px solid black;padding:10px">/g' -e 's/^/<tr><th style="width:150px;max-width:150px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;border:1px solid black;padding:10px">/' -e 's/$/<\/th><\/tr>/' >> $gateway_file
cat $tsv_output_file | sed -e 1d -e 's/\t/<\/td><td style="width:150px;max-width:150px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;border:1px solid black;padding:10px">/g' -e 's/^/<tr><td style="width:150px;max-width:150px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;border:1px solid black;padding:10px">/' -e 's/$/<\/td><\/tr>/' >> $gateway_file
    echo "</table>" >> $gateway_file

    # Generate a summary HTML file for the Gateway to present this info to the user
    html_file=${output_directory}/${repertoire_id}.html

    # Generate the HTML main block
    printf '<!DOCTYPE HTML5>\n' > ${html_file}
    printf '<html lang="en" dir="ltr">' >> ${html_file}

    # Generate a normal looking iReceptor header
    printf '<head>\n' >>  ${html_file}
    cat ${output_directory}/assets/head-template.html >> ${html_file}
    printf "<title>Stats: %s</title>\n" ${title_string} >> ${html_file}
    printf '</head>\n' >>  ${html_file}

    # Generate an iReceptor top bar for the page
    cat ${output_directory}/assets/top-bar-template.html >> ${html_file}

    # Generate a normal looking iReceptor header
    printf '<div class="container job_container">'  >> ${html_file}

	printf "<h2>Stats: %s</h2>\n" ${title_string} >> ${html_file}
    # End of main div container
    printf '</div>' >> ${html_file}

    # Use the normal iReceptor footer.
    cat ${output_directory}/assets/footer.html >> ${html_file}

    # Generate end body end HTML
    printf '</body>' >> ${html_file}
    printf '</html>' >> ${html_file}
}

# Set up the required variables. An iReceptor Gateway download consists
# of both an "info.txt" file that describes the download as well as an
# AIRR manifest JSON file that describes the relationships between
# AIRR Repertoire JSON files and AIRR TSV files.
INFO_FILE="info.txt"
AIRR_MANIFEST_FILE="AIRR-manifest.json"


#if [ "${SPLIT_REPERTOIRE}" = "True" ]; then
#    echo -e "IR-INFO:\nIR-INFO: Splitting data by Repertoire"
#    echo "IR-INFO:"
#    # Split the download into single repertoire files, with a directory per
#    # repository and within that a directory per repertoire. This expects the
#    # user to define a function called run_analysis() that will be
#    # called for each repertoire. See the docs in the gateway_utilities.sh file
#    # for parameters to this function.
#    gateway_split_repertoire ${INFO_FILE} ${AIRR_MANIFEST_FILE} ${IR_DOWNLOAD_FILE} ${GATEWAY_ANALYSIS_DIR}
#    gateway_run_analysis ${INFO_FILE} ${AIRR_MANIFEST_FILE} ${GATEWAY_ANALYSIS_DIR}
#    gateway_cleanup ${IR_DOWNLOAD_FILE} ${AIRR_MANIFEST_FILE} ${GATEWAY_ANALYSIS_DIR}

if [ "${SPLIT_REPERTOIRE}" = "True" ]; then
    echo -e "IR-INFO:\nIR-INFO: Splitting based on repertoire"
    echo "IR-INFO:"

    # Run the analysis with a token repository name of "ADC" since the
    # analysis is being run on data from the entire ADC.
    # repertoire_id is "Total" since it isn't a repertoire analysis.
    repertoire_id="Total"
    repository="AIRRDataCommons"
    outdir=${repository}/${repertoire_id}

    # Unzip the files in the base directory like a normal analysis
    gateway_unzip ${IR_DOWNLOAD_FILE} ${GATEWAY_ANALYSIS_DIR}
    # Also unzip into the analysis dir, as the files in the zip
    # are the files to perform the analysis on.
    gateway_unzip ${IR_DOWNLOAD_FILE} ${GATEWAY_ANALYSIS_DIR}/${outdir}

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
    gateway_cleanup ${IR_DOWNLOAD_FILE} ${AIRR_MANIFEST_FILE} ${GATEWAY_ANALYSIS_DIR}
else
    echo "IR-ERROR: Unknown repertoire operation ${SPLIT_REPERTOIRE}" >&2
    exit 1
fi

# Make sure we are back where we started, although the gateway functions should
# not change the working directory that we are in.
cd ${IR_JOB_DIR}

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
#echo "IR-INFO: Removing analysis output"
#rm -rf ${GATEWAY_ANALYSIS_DIR}

# Cleanup the input data files, don't want to return them as part of the resulting analysis
#echo "IR-INFO: Removing original ZIP file $IR_DOWNLOAD_FILE"
#rm -f $IR_DOWNLOAD_FILE

# Debugging output, print data/time when shell command is finished.
echo "IR-INFO: Junction AA Search finished at: `date`"


