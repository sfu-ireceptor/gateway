# CompAIRR iReceptor Gateway Analysis App shell script

###############################################################################
# Environment processing - print out some info.
###############################################################################
#export IR_GATEWAY_URL="https://gateway-clean.ireceptor.org/"
#export IR_DOWNLOAD_FILE="ir_2025-02-28_2349_67c24b9eb5d26.zip"
#export _tapisExecSystemExecDir="/scratch/ireceptorgw/gateway-clean/jobs/9b59316e-1885-463f-9a62-6261f2dfd1f9-007"
#export IR_GATEWAY_UTIL_DIR="/scratch/ireceptorgw/gateway-clean/gateway_base/gateway_utilities"
echo "IR-INFO: Analysis App request from ${IR_GATEWAY_URL}"
echo "IR-INFO: Processing iReceptor Gateway download file ${IR_DOWNLOAD_FILE}"
# Get the directory where the job is running from Tapis
IR_JOB_DIR=${_tapisExecSystemExecDir}
echo "IR-INFO: Job execution director = ${IR_JOB_DIR}"

###############################################################################
# Parameter processing.
###############################################################################

# The similarity method used, one of 'Count', 'Morisita-Horn', or 'Jaccard'
SIMILARITY_METHOD=$1

if [[ "$SIMILARITY_METHOD" != "Count" && "$SIMILARITY_METHOD" != "Morisita-Horn" && "$SIMILARITY_METHOD" != "Jaccard" ]]; then
    echo "IR-ERROR: Invalid similarity method '${SIMILARITY_METHOD}', must be one of 'Count', 'Morisita-Horn', or 'Jaccard'" 
    exit 1
fi

###############################################################################
# Gateway Utilities set up
###############################################################################

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

###############################################################################
# Use the Gateway Utilities to process each repertoire using the above function
###############################################################################

# All iReceptor Gateway downloads have an info.txt and an AIRR manfiest file
# AIRR-manifest.json. We set these as we use them later.
INFO_FILE="info.txt"
MANIFEST_FILE="AIRR-manifest.json"
IR_INFO="IR-INFO:"

# Make sure we are in the correct job directory.
cd ${IR_JOB_DIR}

# Unzip gateway file
echo -n "${IR_INFO} Unzipping gateway download at "
date
gateway_unzip ${IR_DOWNLOAD_FILE} ${GATEWAY_ANALYSIS_DIR}

# Determine the files to process. We extract the data files from the AIRR-manifest.json
# and store them in an array. The type is one of rearrangement_file, cell_file, clone_file
# Make sure we are in the correct job directory.
pushd ${GATEWAY_ANALYSIS_DIR} >> /dev/null
ANALYSIS_TYPE="rearrangement_file"
data_files=( `python3 ${IR_GATEWAY_UTIL_DIR}/manifest_summary.py ${MANIFEST_FILE} ${ANALYSIS_TYPE}` )
if [ $? -ne 0 ]
then
    echo "IR-ERROR: Could not find manifest file ${MANIFEST_FILE} in ${GATEWAY_ANALYSIS_DIR}"
    exit $?
fi

# Check to make sure we have some data files to process in the manifest file.
echo "${IR_INFO} Data files = ${data_files[@]}"
if [ ${#data_files[@]} -eq 0 ]; then
    echo "IR-ERROR: Could not find any ${ANALYSIS_TYPE} in ${MANIFEST_FILE}"
    exit $?
fi

# Get the repository from the manifest file.
repository_urls=( `python3 ${IR_GATEWAY_UTIL_DIR}/manifest_summary.py ${MANIFEST_FILE} "repository_url"` )
echo "${IR_INFO} Repository URLs = ${repository_urls[@]}"

# Get the Reperotire files from the manifest file.
repertoire_files=( `python3 ${IR_GATEWAY_UTIL_DIR}/manifest_summary.py ${MANIFEST_FILE} "repertoire_file"` )
echo "${IR_INFO} Repertoire files = ${repertoire_files[@]}"

# Create a temporary file
tmp_rearrangement_file=$(mktemp -p .)
echo -e "repertoire_id\tsequence_id\tduplicate_count\tv_call\tj_call\tjunction_aa" > $tmp_rearrangement_file

# For each repository, process the data from it.
count=0
repertoire_total=0
for repository_url in "${repository_urls[@]}"; do
    repertoire_count=0
    # Get the files to process for each repository. This assumes that there is
    # one data file and repertoire file  per repository
    data_file=${data_files[$count]}

    repertoire_file=${repertoire_files[$count]}

    # Get the repository name (FQDN) of the repository
    repository_name=`echo "$repository_url" | awk -F/ '{print $3}'`
    echo "${IR_INFO}"
    echo "${IR_INFO}     Processing data from repository ${repository_name}"
    echo "${IR_INFO}         Repertoire file = ${repertoire_file}"
    echo "${IR_INFO}         Data file = ${data_file}"

    if [ ! -f ${data_file} ]; then
        echo "GW-ERROR: Could not find data file ${data_file}"
        continue
    fi

    # Get the columns required by compairr
    repertoire_id_column=$(head -n 1 ${data_file} | awk -F"\t" -v label=repertoire_id '{for(i=1;i<=NF;i++){if ($i == label){print i}}}')
    sequence_id_column=$(head -n 1 ${data_file} | awk -F"\t" -v label=sequence_id '{for(i=1;i<=NF;i++){if ($i == label){print i}}}')
    duplicate_count_column=$(head -n 1 ${data_file} | awk -F"\t" -v label=duplicate_count '{for(i=1;i<=NF;i++){if ($i == label){print i}}}')
    v_call_column=$(head -n 1 ${data_file} | awk -F"\t" -v label=v_call '{for(i=1;i<=NF;i++){if ($i == label){print i}}}')
    j_call_column=$(head -n 1 ${data_file} | awk -F"\t" -v label=j_call '{for(i=1;i<=NF;i++){if ($i == label){print i}}}')
    junction_aa_column=$(head -n 1 ${data_file} | awk -F"\t" -v label=junction_aa '{for(i=1;i<=NF;i++){if ($i == label){print i}}}')
    # Check to make sure we found them, and if not, print an error message and skip this file.
    if [[ -z "$repertoire_id_column" ]]; then
        echo "IR-ERROR: Could not find required column repertoire_id in ${data_file}"
        continue
    fi
    if [[ -z "$sequence_id_column" ]]; then
        echo "IR-ERROR: Could not find required column sequence_id in ${data_file}"
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
    if [[ -z "$junction_aa_column" ]]; then
        echo "IR-ERROR: Could not find required column junction_aa in ${data_file}"
        continue
    fi

    # Add the columns to a single output file. Take into account the case where
    # an AIRR TSV file is missing duplicate_count. CompAIRR can handle this case
    # if the field is empty but not if it is missing.
    if [[ -z "$duplicate_count_column" ]]; then
        tail -n +2 ${data_file} | awk -F"\t" -v repertoire_id_column=${repertoire_id_column} -v sequence_id_column=${sequence_id_column} -v v_call_column=${v_call_column} -v j_call_column=${j_call_column} -v junction_aa_column=${junction_aa_column} '{printf("%s\t%s\t%s\t%s\t%s\t%s\n",$repertoire_id_column,$sequence_id_column,"",$v_call_column,$j_call_column,$junction_aa_column)}' >> $tmp_rearrangement_file
    else
        tail -n +2 ${data_file} | awk -F"\t" -v repertoire_id_column=${repertoire_id_column} -v sequence_id_column=${sequence_id_column} -v duplicate_count_column=${duplicate_count_column} -v v_call_column=${v_call_column} -v j_call_column=${j_call_column} -v junction_aa_column=${junction_aa_column} '{printf("%s\t%s\t%s\t%s\t%s\t%s\n",$repertoire_id_column,$sequence_id_column,$duplicate_count_column,$v_call_column,$j_call_column,$junction_aa_column)}' >> $tmp_rearrangement_file
    fi

done

# Set up the output in the form the Gateway expects it. For compairr
# we have a single analysis called "Summary"
working_directory="Summary"
repertoire_id="Summary"
working_path=${IR_JOB_DIR}/${GATEWAY_ANALYSIS_DIR}/${working_directory}
mkdir $working_path

if [[ "$SIMILARITY_METHOD" == "Count" ]]; then
    SIMILARITY_ARG=""
elif [[ "$SIMILARITY_METHOD" == "Morisita-Horn" ]]; then
    SIMILARITY_ARG="-s MH"
elif [[ "$SIMILARITY_METHOD" == "Jaccard" ]]; then
    SIMILARITY_ARG="-s Jaccard"
fi


# Run compairr on the result
echo "${IR_INFO}"
echo -n "${IR_INFO} Running compairr at "
date
# -f = ignore counts
# -u = ignore unknown
# -e = ignore empty
# Use SIMILARITY_ARG to choose the correct similarity method.
compairr -f -e -u $SIMILARITY_ARG --matrix ${tmp_rearrangement_file} --out ${working_path}/compairr_matrix.tsv
if [ $? -ne 0 ]
then
    echo "IR-ERROR: CompAIRR failed"
fi
echo -n "${IR_INFO} Done running compairr at "
date
echo "${IR_INFO}"

# Remove the temporary file
#rm -f ${tmp_rearrangement_file}

# Generate the label string.
title_string="Summary"

# Store the title string for this repertoire in the expected file in
# the repertoire directory.
echo "${title_string}" > ${working_path}/${repertoire_id}.txt

# Add a header line to the Gateway rendered HTML file.
echo "<h2>CompAIRR - Repertoire Overlap - ${SIMILARITY_METHOD}</h2>" > ${working_path}/${repertoire_id}-gateway.html

# Use the tcrmatch table generator to generate a nice table.
python3 ${IR_GATEWAY_UTIL_DIR}/tcrmatch-to-html.py --max_width=100 ${working_path}/compairr_matrix.tsv >> ${working_path}/${repertoire_id}-gateway.html

###############################################################################
# Do some housekeeping.
###############################################################################

# Make sure we are back where we started, although the gateway functions should
# not change the base job directory that we started in.
cd ${IR_JOB_DIR}
# We want to move the info.txt file to the main directory.
cp ${GATEWAY_ANALYSIS_DIR}/${INFO_FILE} .

echo -n "IR-INFO: Analysis app finished at "
date
# We want to keep the job error and output files as part of the analysis output.
cp *.err ${GATEWAY_ANALYSIS_DIR}
cp *.out ${GATEWAY_ANALYSIS_DIR}

# Zip up the analysis results for easy download
echo -n "IR-INFO: Ziping the analysis results at "
date
zip -r ${GATEWAY_ANALYSIS_DIR}.zip ${GATEWAY_ANALYSIS_DIR}
mv ${GATEWAY_ANALYSIS_DIR}.zip output/

# We don't want the analysis files to remain - they are in the ZIP file
rm -rf ${GATEWAY_ANALYSIS_DIR}

# Cleanup the input data files, don't want to return them as part of the
# resulting analysis
rm -f $IR_DOWNLOAD_FILE
