#
# Changeo common functions
#
# This script relies upon global variables
# source vdjbase_common.sh
#
# required agave app input and parameters.

# the app
export APP_NAME=vdjbase-singularity

# ----------------------------------------------------------------------------
function expandfile () {
    fileBasename="${1%.*}" # file.txt.gz -> file.txt
    fileExtension="${1##*.}" # file.txt.gz -> gz

    if [ ! -f $1 ]; then
        echo "Could not find input file $1" 1>&2
        exit 1
    fi

    if [ "$fileExtension" == "gz" ]; then
        gunzip $1
        export file=$fileBasename
        # don't archive the intermediate file
    elif [ "$fileExtension" == "bz2" ]; then
        bunzip2 $1
        export file=$fileBasename
    elif [ "$fileExtension" == "zip" ]; then
        unzip -o $1
        export file=$fileBasename
    else
        export file=$1
    fi
}

# prevent Agave from archiving the file
function noArchive() {
    echo $1 >> .agave.archive
}

# ----------------------------------------------------------------------------
# Analysis provenance
function initProvenance() {
    # nothing yet
    echo "initProvenance"
}

# ----------------------------------------------------------------------------
# Workflow functions

function print_versions() {
    echo "VERSIONS:"
    #singularity exec -e ${singularity_image} vdjbase-pipeline -v
    echo -e "\nSTART at $(date)"
}

function print_parameters() {
    echo "Input files:"
    echo "singularity_image=${singularity_image}"
    echo "rearrangement_file=${rearrangement_file}"
    echo ""
    echo "Application parameters:"
    echo "sample_name=${sample_name}"
}

function run_workflow() {
    # Do some provenance - mostly a placeholder for now.
    initProvenance
    echo "Run Workflow"

    # Run DefineClones.py on rearrangement file provided.
    singularity exec -e -B ${PWD}:/data ${singularity_image} vdjbase-pipeline -f /data/${rearrangement_file} -s ${sample_name} -t ${AGAVE_JOB_PROCESSORS_PER_NODE} -o /data

    # List the files in the directory produced by the above job (for provenance).
    ls -l
    echo "Done Workflow"
}
