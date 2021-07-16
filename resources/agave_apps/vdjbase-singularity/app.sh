#
# Wrapper script for running app through the iReceptor Gateway.
# 

# Configuration settings

# These get set by Tapis

# input files
#rearrangement_file="${rearrangement_file}"
#singularity_image="vdjbase_pipeline-1.1.01.sif"
#singularity_image="${singularity}"

# App inputs/variables (will be subsituted by Tapis). If they don't exist
# use command line arguments.

#
# Inputs
#

# Download file is a ZIP archive that is provided by the Gateway and contains
# the results of the users query. This is the data that is being analyzed.
if [ -z "${download_file}" ]; then
        ZIP_FILE=$1
else
        ZIP_FILE=${download_file}
fi

# Uncompress the zip file. The Gateway ZIP file contains an info.txt file that 
# describes the download, and a single AIRR JSON file and AIRR TSV file from
# each repository where data was found.
echo "Extracting files started at: `date`"
unzip -o "$ZIP_FILE"

# The Gateway provides information about the download in the file info.txt
INFO_FILE=info.txt

# Determine the files to process. We extract the .tsv files from the info.txt by
# searching for tsv in the info file. For each file that we find, the first string
# space separated is the file name. This is not particularly robust!
tsv_files=( `cat $INFO_FILE | awk -F" " 'BEGIN {count=0} /tsv/ {if (count>0) printf(" %s",$1); else printf("%s", $1); count++}'` )

# For now we assume that there is only one TSV file in any Gateway download.
rearrangement_file="${tsv_files}"
echo "Processing ${rearrangement_file}"

#
# Application parameters
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

# Agave info
AGAVE_JOB_ID=${AGAVE_JOB_ID}
AGAVE_JOB_NAME=${AGAVE_JOB_NAME}
AGAVE_LOG_NAME=${AGAVE_JOB_NAME}-${AGAVE_JOB_ID}
AGAVE_JOB_PROCESSORS_PER_NODE=${AGAVE_JOB_PROCESSORS_PER_NODE}
AGAVE_JOB_MEMORY_PER_NODE=${AGAVE_JOB_MEMORY_PER_NODE}

# ----------------------------------------------------------------------------
# modules
module load singularity

# Get the singularity image from the Gateway
echo "Downloading singularity image from the Gateway"
date
wget https://gateway-analysis.ireceptor.org/singularity/${singularity_image}
echo -n "Singularity file downloaded = "
ls ${singularity_image}
echo "Done ownloading singularity image from the Gateway"
date

# bring in common functions
source ./vdjbase_common.sh

# Start
printf "START at $(date)\n\n"
printf "PROCS = ${AGAVE_JOB_PROCESSORS_PER_NODE}\n\n"
printf "MEM = ${AGAVE_JOB_MEMORY_PER_NODE}\n\n"

# If you want to tell Tapis that the job failed
export JOB_ERROR=1

# Run the workflow (from vdjabse-common.sh)
print_parameters
print_versions
run_workflow

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
