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

# We provide a mechanism for the user to specify a name for the
# processed data - this is a VDJBase functionality.
sample_name="${sample_name}"
echo "Sample name = ${sample_name}"


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

# Load any modules that are required by the App. 
module load singularity
module load scipy-stack

# Load any bash utility functions needed.
source ./gateway_utilities.sh

# The Gateway provides information about the download in the file info.txt
INFO_FILE="info.txt"
MANIFEST_FILE="airr_manifest.json"

# We want a working directory for the Gateway download information.
WORKING_DIR="analysis_output"

# Start
printf "START at $(date)\n\n"
printf "PROCS = ${AGAVE_JOB_PROCESSORS_PER_NODE}\n\n"
printf "MEM = ${AGAVE_JOB_MEMORY_PER_NODE}\n\n"

function run_analysis()
# Parameters:
#     $1 rearrangement file array
#     $2 output directory
#     $3 repository name [string]
#     $4 repertoire_id [optional]
#     $5 repertoire file [optional - required if repertoire_id is provided]
{
    # Use local variables - no scope issues please...
    local array_of_files=$1
    local output_directory=$2
    local repository_name=$3
    echo "Running a Repertoire Analysis on $1"
        # Check to see if we are processing a specific repertoire_id
    if [ "$#" -eq 5 ]; then
        local repertoire_id=$4
        local repertoire_file=$5
        file_string=`python3 ${SCRIPT_DIR}/repertoire_summary.py ${repertoire_file} ${repertoire_id} --separator "_"`
        file_string=${repository_name}_${file_string// /}
        title_string="$(python3 ${SCRIPT_DIR}/repertoire_summary.py ${repertoire_file} ${repertoire_id})"
        # TODO: Fix this, it should not be required.
        title_string=${title_string// /}
    else
        file_string="total"
        title_string="Total"
    fi

    # Run the VDJBase pipeline within the singularity image on each rearrangement file provided.
    for filename in "${array_of_files[@]}"; do
        echo "Running VDJBase on $filename"
	echo "Mapping ${PWD} to /data"
	echo "Asking for ${AGAVE_JOB_PROCESSORS_PER_NODE} threads"
	echo "Storing output in /data/${output_directory}"
        singularity exec -e -B ${PWD}:/data ${SCRIPT_DIR}/${singularity_image} vdjbase-pipeline -f /data/${filename} -t ${AGAVE_JOB_PROCESSORS_PER_NODE} -o /data/${output_directory}
    done
}

# Split the data by repertoire. This creates a directory tree in $WORKING_DIR
# with a directory per repository and within that a director per repertoire in
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

# Uncompress the zip file. The Gateway ZIP file contains an info.txt file that 
# describes the download, and a single AIRR JSON file and AIRR TSV file from
# each repository where data was found.
#echo "Extracting files started at: `date`"
#unzip -o "$ZIP_FILE"


# Determine the files to process. We extract the .tsv files from the info.txt by
# searching for tsv in the info file. For each file that we find, the first string
# space separated is the file name. This is not particularly robust!
#tsv_files=( `cat $INFO_FILE | awk -F" " 'BEGIN {count=0} /tsv/ {if (count>0) printf(" %s",$1); else printf("%s", $1); count++}'` )

# For now we assume that there is only one TSV file in any Gateway download.
#rearrangement_file="${tsv_files}"
#echo "Processing ${rearrangement_file}"



# bring in common functions
#source ./vdjbase_common.sh



# Run the workflow (from vdjabse-common.sh)
#print_parameters
#print_versions
#run_workflow

# We don't want to copy around the singularity image everywhere.
rm -f ${singularity_image}
# We don't want to copy around the original data. This is replicated
# in the sample file.
rm -f ${ZIP_FILE}

# End
printf "DONE at $(date)\n\n"
printf "AGAVE callback error = ${AGAVE_JOB_CALLBACK_FAILURE} \n\n"

# Handle AGAVE errors
if [[ $JOB_ERROR -eq 1 ]]; then
    ${AGAVE_JOB_CALLBACK_FAILURE}
fi
