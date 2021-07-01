#
# wrapper script
# for cedar.computecanada.ca
# 

# Configuration settings

# These get set by Tapis

# input files
#singularity_image="${singularity_image}"
#rearrangement_file="${rearrangement_file}"
singularity_image="vdjbase_pipeline-1.1.01.sif"
#singularity_image="${singularity}"
echo "Singularity image = ${singularity_image}"

# app variables (will be subsituted by AGAVE). If they don't exist
# use command line arguments.
if [ -z "${file1}" ]; then
        ZIP_FILE=$1
else
        ZIP_FILE=${file1}
fi

##############################################
# uncompress zip file
echo "Extracting files started at: `date`"
unzip -o "$ZIP_FILE"

# The Gateway provides information about the download in the file info.txt
INFO_FILE=info.txt

# Determine the files to process. We extract the .tsv files from the info.txt
tsv_files=( `cat $INFO_FILE | awk -F" " 'BEGIN {count=0} /tsv/ {if (count>0) printf(" %s",$1); else printf("%s", $1); count++}'` )



rearrangement_file="${tsv_files}"
echo "Processing ${rearrangement_file}"

# application parameters
#sample_name=${sample_name}
sample_name="SAMPLE"

# Agave info
AGAVE_JOB_ID=${AGAVE_JOB_ID}
AGAVE_JOB_NAME=${AGAVE_JOB_NAME}
AGAVE_LOG_NAME=${AGAVE_JOB_NAME}-${AGAVE_JOB_ID}
AGAVE_JOB_PROCESSORS_PER_NODE=${AGAVE_JOB_PROCESSORS_PER_NODE}
AGAVE_JOB_MEMORY_PER_NODE=${AGAVE_JOB_MEMORY_PER_NODE}

# ----------------------------------------------------------------------------
# modules
module load singularity

# bring in common functions
source ./vdjbase_common.sh

# Start
printf "START at $(date)\n\n"
printf "PROCS = ${AGAVE_JOB_PROCESSORS_PER_NODE}\n\n"
printf "MEM = ${AGAVE_JOB_MEMORY_PER_NODE}\n\n"


# If you want to tell Tapis that the job failed
export JOB_ERROR=1

# Run the workflow (from changeo-common.sh)
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

# Handle AGAVE errors
if [[ $JOB_ERROR -eq 1 ]]; then
    ${AGAVE_JOB_CALLBACK_FAILURE}
fi
