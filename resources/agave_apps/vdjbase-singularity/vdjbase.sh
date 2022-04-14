#
# wrapper script
# for cedar.computecanada.ca
# 

# Configuration settings

# These get set by Tapis

# input files
#singularity_image="${singularity_image}"
#rearrangement_file="${rearrangement_file}"
singularity_image="${singularity_image}"
rearrangement_file="${download_file}"

# application parameters
sample_name=${sample_name}

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
export JOB_ERROR=0

# Run the workflow (from changeo-common.sh)
print_parameters
print_versions
run_workflow

# End
printf "DONE at $(date)\n\n"

# Handle AGAVE errors
if [[ $JOB_ERROR -eq 1 ]]; then
    ${AGAVE_JOB_CALLBACK_FAILURE}
fi
