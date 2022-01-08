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

# Get the singularity image from the Gateway
echo "Downloading singularity image from the Gateway"
date
wget -nv https://gateway-analysis.ireceptor.org/singularity/${singularity_image}
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
wget --no-verbose -r -nH --no-parent --cut-dir=1 --reject="index.html*" --reject="robots.txt*" https://gateway-analysis.ireceptor.org/gateway_utilities/
popd > /dev/null
echo "Done downloading iReceptor Gateway Utilities"
date

# Load the iReceptor Gateway bash utility functions.
source ${SCRIPT_DIR}/${GATEWAY_UTIL_DIR}/gateway_utilities.sh

# Load any modules that are required by the App. 
module load singularity
module load scipy-stack

# The Gateway provides information about the download in the file info.txt
INFO_FILE="info.txt"
MANIFEST_FILE="airr_manifest.json"

# We want a working directory for the Gateway download information.
WORKING_DIR="analysis_output"

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
#     $5-$N rearrangement files (bash doesn't like arrays, so the rest of the parameters
#        are considered rearrangement files.
{
    # Use local variables - no scope issues please...
    local output_directory=$1
    local repository_name=$2
    local repertoire_id=$3
    local repertoire_file=$4
    shift
    shift
    shift
    shift
    # Remaining variable are the files to process
    local array_of_files=( $@ )

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
input
    
    for filename in "${array_of_files[@]}"; do
        echo "Running CompAIRR on $filename"
	echo "Mapping ${PWD} to /data"
	echo "Asking for ${AGAVE_JOB_PROCESSORS_PER_NODE} threads"
	echo "Storing output in /data/${output_directory}"
	# Run CompAIRR
        singularity exec -e -B ${PWD}:/data ${SCRIPT_DIR}/${singularity_image} compairr -u -t ${AGAVE_JOB_PROCESSORS_PER_NODE} --cluster --difference 0 --ignore-counts /data/${filename} -o /data/${output_directory}/${file_string}.tsv
	# Remove the repertoire TSV file, we don't want to keep it around as part of the analysis results.
	rm -f ${PWD}/${filename}
    done
    printf "Done Repertoire Analysis on ${array_of_files[@]} at $(date)\n\n"
}

# Split the data by repertoire. This creates a directory tree in $WORKING_DIR
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
gateway_split_repertoire ${INFO_FILE} ${MANIFEST_FILE} ${ZIP_FILE} ${WORKING_DIR}

# Make sure we are back where we started, although the gateway functions should
# not change the working directory that we are in.
cd ${SCRIPT_DIR}

# We want to move the info.txt to the main directory. The Gateway expects this.
cp ${WORKING_DIR}/${INFO_FILE} .

# We want the job error and output files to be part of the analysis so copy them
cp *.err ${WORKING_DIR}
cp *.out ${WORKING_DIR}

# Zip up the analysis results for easy download
echo "ZIPing analysis results"
zip -r ${WORKING_DIR}.zip ${WORKING_DIR}

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
