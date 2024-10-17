#
# Wrapper script for running app through the iReceptor Gateway.
# 

# Get the script directory where all the code is.
SCRIPT_DIR=${_tapisExecSystemExecDir}
echo "IR-INFO: Running job from ${SCRIPT_DIR}"

########################################################################
# Tapis configuration/settings
########################################################################

#
# Tapis App Parameters: Will be subsituted by Tapis as command line 
# arguments to this shell script.
#
# First (and only) argument is the TCRmatch threshold to use.
#

TCR_MATCH_THRESHOLD=$1

#
# Tapis App Inputs
#

# Download file is a ZIP archive that is provided by the Gateway and contains
# the results of the users query. This is the data that is being analyzed.
ZIP_FILE=${IR_DOWNLOAD_FILE}

# Tapis environment variable IR_GATEWYA_URL contains the URL of the source gateway. Use
# this to gather iReceptor Gateway specific resources if needed.
GATEWAY_URL="${IR_GATEWAY_URL}"

########################################################################
# Done Tapis setup/processing.
########################################################################
echo "IR-INFO: Using Gateway ${GATEWAY_URL}"

# Report where we get the Gateway utilities from
GATEWAY_UTIL_DIR=${IR_GATEWAY_UTIL_DIR}
echo "IR-INFO: Using iReceptor Gateway Utilities from ${GATEWAY_UTIL_DIR}"

# Load the iReceptor Gateway bash utility functions.
source ${GATEWAY_UTIL_DIR}/gateway_utilities.sh
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
        echo "IR-ERROR: GATEWAY_ANALYSIS_DIR not defined, gateway_utilities not loaded correctly."
        exit 1
fi
echo "IR-INFO: Done loading iReceptor Gateway Utilities"

# The Gateway provides information about the download in the file info.txt
INFO_FILE="info.txt"
MANIFEST_FILE="AIRR-manifest.json"

# Start
printf "IR-INFO:\nIR-INFO:\n"
printf "IR-INFO: START at $(date)\n"
printf "IR-INFO: PROCS = ${_tapisCoresPerNode}\n"
printf "IR-INFO: MEM = ${_tapisMemoryMB}\n"
printf "IR-INFO: MAX RUNTIME = ${_tapisMaxMinutes}\n"
printf "IR-INFO: SLURM JOB ID = ${SLURM_JOB_ID}\n"
printf "IR-INFO: ZIP FILE = ${ZIP_FILE}\n"
printf "IR-INFO: "
lscpu | grep "Model name"
printf "IR-INFO:\nIR-INFO:\n"

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
#     $6 analysis type
{
    # Use local variables - no scope issues please...
    local output_directory=$1
    local repository_name=$2
    local repertoire_id=$3
    local repertoire_file=$4
    local manifest_file=$5
    local analysis_type=$6
    echo "IR-INFO: Running a TCRMatch Repertoire Analysis with manifest ${manifest_file}"

    local rearrangement_files=( `python3 ${GATEWAY_UTIL_DIR}/manifest_summary.py ${manifest_file} "rearrangement_file"` )
    if [ ${#rearrangement_files[@]} != 1 ]
    then
        echo "IR_ERROR: TCRMatch analysis only works with a single rearrangement file."
        return
    fi
    local rearrangement_file=${rearrangement_files[0]}
    echo "IR-INFO:     Using rearrangement file ${rearrangement_file}"

    # Check to see if we are processing a specific repertoire_id
    if [ "${repertoire_id}" != "NULL" ]; then
        file_string=`python3 ${GATEWAY_UTIL_DIR}/repertoire_summary.py ${repertoire_file} ${repertoire_id} --separator "_"`
        title_string="$(python3 ${GATEWAY_UTIL_DIR}/repertoire_summary.py ${repertoire_file} ${repertoire_id})"
    else
        file_string="total"
        title_string="Total"
    fi

    # Clean up special characters in file and title strings.
    file_string=`echo ${repository_name}_${file_string} | sed "s/[!@#$%^&*() :/-]/_/g"`
    # TODO: Fix this, it should not be required.
    title_string=`echo ${title_string} | sed "s/[ ]//g"`

    # Run TCRMatch on each rearrangement file provided.
    echo "IR-INFO: Running TCRMatch on $rearrangement_file"
    echo "IR-INFO: Mapping ${PWD} to /data"
    echo "IR-INFO: Asking for ${AGAVE_JOB_PROCESSORS_PER_NODE} threads"
    echo "IR-INFO: Storing output in /data/${output_directory}"

    # Use a temporary file for output
    #CDR3_FILE=${output_directory}/${repertoire_id}_cdr3.tsv
    #rm -f ${CDR3_FILE}

    # Extract cdr3_aa column if it exists - for now we are not doing this. 
    #python3 ${IR_GATEWAY_UTIL_DIR}/preprocess.py ${output_directory}/${rearrangement_file} cdr3_aa \
    #    | grep -v nan \
    #    | sort -u \
    #    | egrep -v "\*" \
    #    >> $CDR3_FILE
    #if [ $? -ne 0 ]
    #then
    #    echo "IR-ERROR: Unable to extract CDR3s from ${output_directory}/${rearrangement_file}"
    #fi

    # Use a temporary file for output
    JUNCTION_FILE=${output_directory}/${repertoire_id}_junction.tsv
    rm -f ${JUNCTION_FILE}

    # Extract junction_aa column if it exists
    #
    # grep -v nan - We extract all lines that are 'nan' as that is what is produced
    # if preprocess doesn't find a value in the field.
    #
    # TCRMatch needs CDR3s so if we are processing Junctions we need to strip off
    # the first and last AA.
    # For junctions if the first character is a C and the last is either W or F 
    # (/^C.*[WF]$/) we assume it is a full junction and strip of those characters 
    # with the sed regular expression substuution s/^.\(.*\).$/\1/'
    #
    # sort -u - We only want to search once per CDR3
    #
    # egrep -v "\*" - Remove any CDR3s with special characters
    python3 ${IR_GATEWAY_UTIL_DIR}/preprocess.py ${output_directory}/${rearrangement_file} junction_aa \
        | grep -v nan \
        | sed '/^C.*[WF]$/ s/^.\(.*\).$/\1/' \
        | sort -u \
        | egrep "^[ABCDEFGHIKLMNPQRSTVWYZ]*$" \
        >> $JUNCTION_FILE
    if [ $? -ne 0 ]
    then
        echo "IR-ERROR: Unable to extract Junctions from ${output_directory}/${rearrangement_file}"
    fi

    # Run TCRMatch on CDR3s
    #echo -n "IR-INFO: Running TCRMatch on ${CDR3_FILE} - "
    #date
    #/TCRMatch/tcrmatch \
    #    -i ${CDR3_FILE} \
    #    -d /TCRMatch/data/IEDB_data.tsv -t 1\
    #    >> ${output_directory}/${repertoire_id}_epitope.tsv
    #if [ $? -ne 0 ]
    #then
    #    echo "IR-ERROR: TCRMatch failed on file ${CDR3_FILE}"
    #fi

    # Run TCRMatch on Junctions
    echo -n "IR-INFO: Running TCRMatch on ${JUNCTION_FILE} - "
    date
    /TCRMatch/tcrmatch \
        -i ${JUNCTION_FILE} \
        -d /TCRMatch/data/IEDB_data.tsv -t 1\
        -s ${TCR_MATCH_THRESHOLD} \
        > ${output_directory}/${repertoire_id}_epitope.tsv
    if [ $? -ne 0 ]
    then
        echo "IR-ERROR: TCRMatch failed on file ${JUNCTION_FILE}"
    fi
    echo -n "IR-INFO: Done running TCRMatch on ${JUNCTION_FILE} - "
    date

    # For each junction_aa found, extract the rearrangements that have that junction_aa
    # Loop through the input TSV file line by line
    search_column="junction_aa"
    column_index=$(head -1 "${output_directory}/${rearrangement_file}" | tr '\t' '\n' | awk -v header="$search_column" '{if ($1 == header) print NR}')
    while IFS=$'\t' read -r column1 column2 column3 column4 column5 other_columns; do

        # Search for a match in the specific "junction_aa" column with the pattern "C<value>F" or "C<value>W"
        results=$(awk -v FS="\t" -v col="$column_index" -v value="$column1" \
            'NR > 1 && $col ~ "^C" value "(F|W)$" { print }' "${output_directory}/${rearrangement_file}")

        if [ -n "$results" ]; then
            output_file="${output_directory}/${column1}_${column4}_${column5}.tsv"
            echo "IR-INFO: Writing results for ${column1} to $output_file"
            head -n 1 ${output_directory}/${rearrangement_file} > $output_file
            echo "$results" >> "$output_file"
        else
            echo "IR-INFO: Warning, could not find ${column1} in ${output_directory}/${rearrangement_file}"
        fi
    done < "${output_directory}/${repertoire_id}_epitope.tsv"

    # Generate a summary HTML file for the Gateway to present this info to the user
    html_file=${output_directory}/${repertoire_id}.html

    # Generate the HTML main block
    printf '<!DOCTYPE HTML5>\n' > ${html_file}
    printf '<html lang="en" dir="ltr">' >> ${html_file}

    # Generate a normal looking iReceptor header
    printf '<head>\n' >>  ${html_file}
    cat ${output_directory}/assets/head-template.html >> ${html_file}
    printf "<title>TCRMatch: %s</title>\n" ${title_string} >> ${html_file}
    printf '</head>\n' >>  ${html_file}

    # Generate an iReceptor top bar for the page
    cat ${output_directory}/assets/top-bar-template.html >> ${html_file}

    # Generate a normal looking iReceptor header
    printf '<div class="container job_container">'  >> ${html_file}
    printf "<h2>TCRMatch: %s</h2>\n" ${title_string} >> ${html_file}

    printf "<h2>Analysis</h2>\n" >> ${html_file}
    printf "<h3>TCRMatch Summary</h3>\n" >> ${html_file}
    
    echo "<ul>" >> ${html_file}

    echo -n "<li>Number of unique CDR3s: " >> ${html_file}
    wc -l ${JUNCTION_FILE} | cut -f 1 -d " " >> ${html_file}
    echo "</li>" >> ${html_file}

    echo -n "<li>Number of CDR3/epitope matches: " >> ${html_file}
    # Skip the header and count
    tail -n +2 ${output_directory}/${repertoire_id}_epitope.tsv | wc -l | cut -f 1 -d " " >> ${html_file}
    echo "</li>" >> ${html_file}

    echo -n "<li>Number of unique CDR3/epitope matches: " >> ${html_file}
    # Skip the header, extract the CDR3 column, sort unique and count.
    tail -n +2 ${output_directory}/${repertoire_id}_epitope.tsv | cut -f 2 | sort -u | wc -l | cut -f 1 -d " " >> ${html_file}
    echo "</li>" >> ${html_file}

    echo "</ul>" >> ${html_file}


    printf "<h3>%s</h3>\n" ${title_string} >> ${html_file}
    python3 ${IR_GATEWAY_UTIL_DIR}/tcrmatch-to-html.py ${output_directory}/${repertoire_id}_epitope.tsv >> ${html_file}
    if [ $? -ne 0 ]
    then
        echo "IR-ERROR: TCRMatch could not generate HTML file for ${repertoire_id}_epitope.tsv"
        echo "ERROR occurred generating HTML report generation" >> ${html_file}
    fi

    # End of main div container
    printf '</div>' >> ${html_file}

    # Use the normal iReceptor footer.
    cat ${output_directory}/assets/footer.html >> ${html_file}

    # Generate end body end HTML
    printf '</body>' >> ${html_file}
    printf '</html>' >> ${html_file}
    
    # Generate a summary HTML file for the Gateway to present this info to the user
    html_file=${output_directory}/${repertoire_id}-gateway.html

    printf "<h2>TCRMatch: %s</h2>\n" ${title_string} >> ${html_file}

    printf "<h2>Analysis</h2>\n" >> ${html_file}
    printf "<h3>TCRMatch Summary</h3>\n" >> ${html_file}
    
    echo "<ul>" >> ${html_file}

    echo -n "<li>Number of unique CDR3s: " >> ${html_file}
    wc -l ${JUNCTION_FILE} | cut -f 1 -d " " >> ${html_file}
    echo "</li>" >> ${html_file}

    echo -n "<li>Number of CDR3/epitope matches: " >> ${html_file}
    # Skip the header and count
    tail -n +2 ${output_directory}/${repertoire_id}_epitope.tsv | wc -l | cut -f 1 -d " " >> ${html_file}
    echo "</li>" >> ${html_file}

    echo -n "<li>Number of unique CDR3/epitope matches: " >> ${html_file}
    # Skip the header, extract the CDR3 column, sort unique and count.
    tail -n +2 ${output_directory}/${repertoire_id}_epitope.tsv | cut -f 2 | sort -u | wc -l | cut -f 1 -d " " >> ${html_file}
    echo "</li>" >> ${html_file}

    echo "</ul>" >> ${html_file}


    printf "<h3>%s</h3>\n" ${title_string} >> ${html_file}
    python3 ${IR_GATEWAY_UTIL_DIR}/tcrmatch-to-html.py ${output_directory}/${repertoire_id}_epitope.tsv >> ${html_file}
    if [ $? -ne 0 ]
    then
        echo "IR-ERROR: TCRMatch could not generate HTML file for ${repertoire_id}_epitope.tsv"
        echo "ERROR occurred generating HTML report generation" >> ${html_file}
    fi

    # Add the required label file for the Gateway to present the results as a summary.
    label_file=${output_directory}/${repertoire_id}.txt
    echo "IR-INFO: Generating label file ${label_file}"
    echo "${title_string}" > ${label_file}
    if [ $? -ne 0 ]
    then
        echo "IR-ERROR: Could not generate label file ${label_file}"
    fi
    echo "IR-INFO: Done generating label file ${label_file}"

    # We don't want to keep around the generated data files or the manifest file.
    rm -f ${output_directory}/${rearrangement_file} ${output_directory}/${manifest_file}

    # done
    printf "IR-INFO: Done running Repertoire Analysis on ${rearrangement_file} at $(date)\n"
}

# Split the data by repertoire. This creates a directory tree in $GATEWAY_ANALYSIS_DIR
# with a directory per repository and within that a directory per repertoire in
# that repository. In each repertoire directory there will exist an AIRR manifest
# file and the data (as described in the manifest file) from that repertoire.
#
# The gateway utilities use a callback mechanism, calling the
# function run_analysis() on each repertoire. The run_analysis function
# is locally provided and should do all of the processing for a single
# repertoire.
#
# So the pipeline is:
#    - Split the data into repertoire directories as described above
#    - Run the analysis on each repertoire, calling run_analysis for each
#    - Cleanup the intermediate files created by the split process.
# run_analysis() is defined above.
gateway_split_repertoire ${INFO_FILE} ${MANIFEST_FILE} ${ZIP_FILE} ${GATEWAY_ANALYSIS_DIR} "rearrangement_file" 
gateway_run_analysis ${INFO_FILE} ${MANIFEST_FILE} ${GATEWAY_ANALYSIS_DIR} "rearrangement_file"
gateway_cleanup ${ZIP_FILE} ${MANIFEST_FILE} ${GATEWAY_ANALYSIS_DIR}

# Make sure we are back where we started, although the gateway functions should
# not change the working directory that we are in.
cd ${SCRIPT_DIR}

# We want to move the info.txt file to the main directory.
cp ${GATEWAY_ANALYSIS_DIR}/${INFO_FILE} .

# We want to keep the job error and output files as part of the analysis output.
cp *.err ${GATEWAY_ANALYSIS_DIR}
cp *.out ${GATEWAY_ANALYSIS_DIR}

# Zip up the analysis results for easy download
echo "IR-INFO: ZIPing analysis results - $(date)"
zip -r ${GATEWAY_ANALYSIS_DIR}.zip ${GATEWAY_ANALYSIS_DIR}
mv ${GATEWAY_ANALYSIS_DIR}.zip output/
echo "IR-INFO: Done ZIPing analysis results - $(date)"

# We don't want the analysis files to remain - they are in the ZIP file
echo "IR-INFO: Removing analysis output"
rm -rf ${GATEWAY_ANALYSIS_DIR}

# Cleanup the input data files, don't want to return them as part of the resulting analysis
echo "IR-INFO: Removing original ZIP file $ZIP_FILE"
rm -f $ZIP_FILE

# End
printf "IR-INFO: DONE at $(date)\n\n"

