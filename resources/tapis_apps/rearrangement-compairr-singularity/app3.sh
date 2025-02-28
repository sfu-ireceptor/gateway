# A basic iReceptor Gateway Analysis App shell script

###############################################################################
# Environment processing - print out some info.
###############################################################################
echo "IR-INFO: Analysis App request from ${IR_GATEWAY_URL}"
echo "IR-INFO: Processing iReceptor Gateway download file ${IR_DOWNLOAD_FILE}"
# Get the directory where the job is running from Tapis
IR_JOB_DIR=${_tapisExecSystemExecDir}
echo "IR-INFO: Job execution director = ${IR_JOB_DIR}"

###############################################################################
# Parameter processing - there aren't any for this App.
###############################################################################

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
# Define a function to run on each repertoire
###############################################################################

function run_analysis()
{
	# Use local variables for clarity...
	local working_directory=$1
	local repository_name=$2
	local repertoire_id=$3
	local repertoire_file=$4
    local manifest_file=$5
    local analysis_type=$6

    # Print out a message to the log file.
    echo "IR-INFO: Running on repertoire = ${repertoire_id}"

    # Determine the fully qualified path of where the data is as a shortcut.
    working_path=${IR_JOB_DIR}/${GATEWAY_ANALYSIS_DIR}/${working_directory}
    compairr -v

    # Generate the label string.
    title_string="$(python3 ${IR_GATEWAY_UTIL_DIR}/repertoire_summary.py ${repertoire_file} ${repertoire_id})"

    # Store the title string for this repertoire in the expected file in
    # the repertoire directory.
    echo "${title_string}" > ${working_path}/${repertoire_id}.txt

    # Add a header line to the Gateway rendered HTML file.
    echo "<h2>${title_string}</h2>" > ${working_path}/${repertoire_id}-gateway.html
    echo "<p>Your analysis output for this repertoire goes here!</p>" >> ${working_path}/${repertoire_id}-gateway.html
}

###############################################################################
# Use the Gateway Utilities to process each repertoire using the above function
###############################################################################

# All iReceptor Gateway downloads have an info.txt and an AIRR manfiest file
# AIRR-manifest.json. We set these as we use them later.
INFO_FILE="info.txt"
AIRR_MANIFEST_FILE="AIRR-manifest.json"

# Split the repertoires into the directory structure
echo -n "IR-INFO: Splitting repertoires at "
date
gateway_split_repertoire ${INFO_FILE} ${AIRR_MANIFEST_FILE} ${IR_DOWNLOAD_FILE} ${GATEWAY_ANALYSIS_DIR}

# Run the run_analysis function on every repertoire in the directory structure
echo -n "IR-INFO: Running the analysis at "
date
gateway_run_analysis ${INFO_FILE} ${AIRR_MANIFEST_FILE} ${GATEWAY_ANALYSIS_DIR}

# Cleanup the data files in the directory stucture
echo -n "IR-INFO: Cleaning up the data at "
date
gateway_cleanup ${IR_DOWNLOAD_FILE} ${AIRR_MANIFEST_FILE} ${GATEWAY_ANALYSIS_DIR}

###############################################################################
# Do some housekeeping.
###############################################################################

# Make sure we are back where we started, although the gateway functions should
# not change the base job directory that we started in.
cd ${IR_JOB_DIR}
# We want to move the info.txt file to the main directory.
cp ${GATEWAY_ANALYSIS_DIR}/${INFO_FILE} .

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
echo -n "IR-INFO: Analysis app finished at "
date
