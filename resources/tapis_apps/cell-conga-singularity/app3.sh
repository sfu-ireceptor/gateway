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
# Tapis App Parameters: Will be subsituted by Tapis. None for this App
#

#
# Tapis App Inputs
#

# Tapis environment variable IR_GATEWAY_URL contains the URL of the source gateway. Use
# this to gather iReceptor Gateway specific resources if needed.
GATEWAY_URL="${IR_GATEWAY_URL}"

# Download file is a ZIP archive that is provided by the Gateway and contains
# the results of the users query. This is the data that is being analyzed.
ZIP_FILE=${IR_DOWNLOAD_FILE}

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
printf "IR-INFO: \nIR-INFO: \n"
printf "IR-INFO: START at $(date)\n"
printf "IR-INFO: PROCS = ${_tapisCoresPerNode}\n"
printf "IR-INFO: MEM = ${_tapisMemoryMB}\n"
printf "IR-INFO: MAX RUNTIME = ${_tapisMaxMinutes}\n"
printf "IR-INFO: SLURM JOB ID = ${SLURM_JOB_ID}\n"
printf "IR-INFO: ZIP FILE = ${ZIP_FILE}\n"
printf "IR-INFO: "
lscpu | grep "Model name"
printf "IR-INFO: \nIR-INFO: \n"

# This function is called by the iReceptor Gateway utilities function gateway_run_analysis
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
    echo "IR-INFO: Running a Cell Repertoire Analysis on ${repertoire_id}"
    echo "IR-INFO:     Using manifest file ${manifest_file}"

    # Get a list of rearrangement files to process from the manifest.
    local cell_files=( `python3 ${GATEWAY_UTIL_DIR}/manifest_summary.py ${manifest_file} "cell_file"` )
    if [ $? -ne 0 ]
    then
        echo "IR-ERROR: Could not process manifest file ${manifest_file}"
        echo "IR-ERROR: Processing for repertoire ${repertoire_id} not completed."
        return
    fi
    if [ ${#cell_files[@]} != 1 ]
    then
        echo "IR_ERROR: Conga cell analysis only works with a single cell file."
        echo "IR-ERROR: Processing for repertoire ${repertoire_id} not completed."
        return
    fi
    local cell_file_count=${#cell_files[@]}
    local cell_file=${cell_files[0]}
    echo "IR-INFO:     Using cell file ${cell_file}"
    local gex_files=( `python3 ${GATEWAY_UTIL_DIR}/manifest_summary.py ${manifest_file} "expression_file"` )
    if [ ${#gex_files[@]} != 1 ]
    then
        echo "IR_ERROR: Conga cell analysis only works with a single expression file."
        echo "IR-ERROR: Processing for repertoire ${repertoire_id} not completed."
        return
    fi
    local gex_file=${gex_files[0]}
    echo "IR-INFO:     Using gex file ${gex_file}"
    local rearrangement_files=( `python3 ${GATEWAY_UTIL_DIR}/manifest_summary.py ${manifest_file} "rearrangement_file"` )
    if [ ${#rearrangement_files[@]} != 1 ]
    then
        echo "IR_ERROR: Conga cell analysis only works with a single rearrangement file."
        echo "IR-ERROR: Processing for repertoire ${repertoire_id} not completed."
        return
    fi
    local rearrangement_file=${rearrangement_files[0]}
    echo "IR-INFO:     Using rearrangement file ${rearrangement_files}"

    # Keep track of our base file string, use repertoire_id
    file_string=${repertoire_id}

    # Check to see if we are processing a specific repertoire_id
    # If so generate an appropriate title
    if [ "${repertoire_id}" != "NULL" ]; then
        title_string="$(python3 ${GATEWAY_UTIL_DIR}/repertoire_summary.py ${repertoire_file} ${repertoire_id})"
    else
        file_string="total"
        title_string="Total"
    fi

    # Clean up special characters in file and title strings.
    file_string=$(echo ${repository_name}_${file_string} | tr -dc "[:alnum:]._-")

    # TODO: Fix this, it should not be required.
    title_string=`echo ${title_string} | sed "s/[ ]//g"`

    # Run the Conga pipeline within the singularity image on each rearrangement file provided.
    echo "IR-INFO: Running Conga on $cell_file"
    echo "IR-INFO: Mapping ${PWD} to /data"
    echo "IR-INFO: Asking for ${_tapisCoresPerNode} threads"
    echo "IR-INFO: Storing output in /data/${output_directory}"

    # Convert Rearrangement file to a 10X Contig file. This uses code in the container
    # for this App that is specific to iReceptor.
    CONTIG_PREFIX=10x-contig
    python3 /gitrepos/conga/ireceptor/rearrangements-to-10x.py ${output_directory}/${rearrangement_file} ${output_directory}/${CONTIG_PREFIX}.csv
    if [ $? -ne 0 ]
    then
        echo "IR-ERROR: Could not generate rearrangements"
        echo "IR-ERROR: Processing for repertoire ${repertoire_id} (${title_string}) not completed."
        return
    fi

    # Get the field that links cell data to rearrangement data.
    repertoire_link_field=`python3 ${GATEWAY_UTIL_DIR}/repertoire_field.py --json_filename ${repertoire_file} --repertoire_field data_processing.data_processing_id --repertoire_id ${repertoire_id}`

    # Get the first N cellds from the cell data file. We use the adc_annotation_cell_id 
    # field to check against the rerrangement cell_id.
    cells_to_check=20
    cell_ids=`python3 ${GATEWAY_UTIL_DIR}/preprocess-json.py ${output_directory}/${cell_file} Cell adc_annotation_cell_id | head -${cells_to_check} | awk '{if (NR>1) printf("|%s", $1); else printf("%s", $1)}'`

    # Get the column number of the v_call field in the rearrangement file.
    column_header='v_call'
    column_number=`cat ${output_directory}/${rearrangement_file} | head -n 1 | awk -F"\t" -v label=${column_header} '{for(i=1;i<=NF;i++){if ($i == label){print i}}}'`

    # Check the first N cell's in the rearrangement file and extract the list
    # of cell type in the data (IG or TR)
    repertoire_locus=( `egrep "${cell_ids}" ${output_directory}/${rearrangement_file} | cut -f ${column_number} | tail --lines=+2 | awk '{printf("%s\n", substr($1,0,2))}' | sort -u | awk '{printf("%s  ",$0)}'` )
    if [ $? -ne 0 ]
    then
        echo "IR-ERROR: Could not get a cell type for repertoire ${repertoire_id}"
        echo "IR-ERROR: Processing for repertoire ${repertoire_id} (${title_string}) not completed."
        return
    fi

    # Check to see if there is only one cell type in the data.
    if [ ${#repertoire_locus[@]} != 1 ]
    then
        echo "IR-ERROR: Conga cell analysis requires a single cell type (repertoire_id = ${repertoire_id}, cell types = ${repertoire_locus[@]})."
        echo "IR-ERROR: Processing for repertoire ${repertoire_id} (${title_string}) not completed."
        return
    fi

    # If there is only one, check to see if it is TR cell type, if so then we are good,
    # if not it is an error.
    repertoire_locus=${repertoire_locus[0]}

    if [ "${repertoire_locus}" == "TR" ]
    then
        conga_type="human"
    # Code to add when Conga's IG processing gets better.    
    #elif [ "${repertoire_locus}" == "IG" ]
    #then
    #    conga_type="human_ig"
    else
        echo "IR-ERROR: Conga cell analysis can only run on TR repertoires (repertoire_id = ${repertoire_id}, cell type = ${repertoire_locus})."
        echo "IR-ERROR: Processing for repertoire ${repertoire_id} (${title_string}) not completed."
        return
    fi
    echo "IR-INFO: Column header = ${column_header}"
    echo "IR-INFO: Column number = ${column_number}"
    echo "IR-INFO: Locus = ${repertoire_locus[@]}"
    echo "IR-INFO: Data Processing ID = ${repertoire_link_field}"
    echo "IR-INFO: Conga analysis type = ${conga_type}"

    # Run Conga setup for processing.
    #singularity exec --cleanenv --env PYTHONNOUSERSITE=1 -B ${PWD}:/data ${SCRIPT_DIR}/${singularity_image} python3 /gitrepos/conga/scripts/setup_10x_for_conga.py --filtered_contig_annotations_csvfile /data/${output_directory}/${CONTIG_PREFIX}.csv --organism ${conga_type}
    python3 /gitrepos/conga/scripts/setup_10x_for_conga.py --filtered_contig_annotations_csvfile ${PWD}/${output_directory}/${CONTIG_PREFIX}.csv --organism ${conga_type}
    if [ $? -ne 0 ]
    then
        echo "IR-ERROR: Conga setup_10x_for_conga failed on ${output_directory}/${CONTIG_PREFIX}.csv"
        echo "IR-ERROR: Processing for repertoire ${repertoire_id} (${title_string}) not completed."
        return
    fi

    # Run Conga proper on the data.
    #singularity exec --cleanenv --env PYTHONNOUSERSITE=1 -B ${PWD}:/data ${SCRIPT_DIR}/${singularity_image} python3 /gitrepos/conga/scripts/run_conga.py --all --organism ${conga_type} --clones_file /data/${output_directory}/${CONTIG_PREFIX}_tcrdist_clones.tsv --gex_data /data/${output_directory}/${gex_file} --gex_data_type h5ad --outfile_prefix /data/${output_directory}/${file_string}
    python3 /gitrepos/conga/scripts/run_conga.py --all --organism ${conga_type} --clones_file ${PWD}/${output_directory}/${CONTIG_PREFIX}_tcrdist_clones.tsv --gex_data ${PWD}/${output_directory}/${gex_file} --gex_data_type h5ad --outfile_prefix ${PWD}/${output_directory}/${file_string}
    if [ $? -ne 0 ]
    then
        echo "IR-ERROR: Conga failed on ${CONTIG_PREFIX}_tcrdist_clones.tsv and ${gex_file}"
        echo "IR-ERROR: Processing for repertoire ${repertoire_id} not completed."
        return
    fi

    # Generate a summary HTML file for the Gateway to present this info to the user
    html_file=${output_directory}/${repertoire_id}.html

    # Generate the HTML main block
    printf '<!DOCTYPE HTML5>\n' > ${html_file}
    printf '<html lang="en" dir="ltr">' >> ${html_file}

    # Generate a normal looking iReceptor header
    printf '<head>\n' >>  ${html_file}
    cat ${output_directory}/assets/head-template.html >> ${html_file}
    printf "<title>Conga: %s</title>\n" ${title_string} >> ${html_file}
    printf '</head>\n' >>  ${html_file}

    # Generate an iReceptor top bar for the page
    cat ${output_directory}/assets/top-bar-template.html >> ${html_file}

    # Generate a normal looking iReceptor header
    printf '<div class="container job_container">'  >> ${html_file}
    printf "<h2>Conga: %s</h2>\n" ${title_string} >> ${html_file}

    printf "<h2>Analysis</h2>\n" >> ${html_file}

    printf "<h3>Conga Analysis: %s</h3>\n" ${title_string} >> ${html_file}
    sed -i 's/gex_clusters_tcrdist_trees.png/gex_clusters_tcrdist_trees.svg/g' ${output_directory}/${file_string}_results_summary.html
    sed -i 's/conga_threshold_tcrdist_tree.png/conga_threshold_tcrdist_tree.svg/g' ${output_directory}/${file_string}_results_summary.html
    printf '<iframe src="%s" width="100%%", height="700px"></iframe>\n' ${file_string}_results_summary.html >> ${html_file}

    # End of main div container
    printf '</div>' >> ${html_file}

    # Use the normal iReceptor footer.
    cat ${output_directory}/assets/footer.html >> ${html_file}

    # Generate end body end HTML
    printf '</body>' >> ${html_file}
    printf '</html>' >> ${html_file}

    # Generate a summary HTML file for the Gateway to present this info to the user
    html_file=${output_directory}/${repertoire_id}-gateway.html

    printf "<h2>Conga: %s</h2>\n" ${title_string} >> ${html_file}
    printf "<h3>Conga Analysis: %s</h3>\n" ${title_string} >> ${html_file}
    #sed -i 's/gex_clusters_tcrdist_trees.png/gex_clusters_tcrdist_trees.svg/g' ${output_directory}/${file_string}_results_summary.html
    #sed -i 's/conga_threshold_tcrdist_tree.png/conga_threshold_tcrdist_tree.svg/g' ${output_directory}/${file_string}_results_summary.html
    printf '<iframe src="/jobs/view/show?jobid=%s&directory=%s&filename=%s" width="100%%", height="700px"></iframe>\n' ${IR_GATEWAY_JOBID} ${output_directory} ${file_string}_results_summary.html >> ${html_file}

    # Copy the Conga summary report to the gateway expected summary for this repertoire
    #cp ${output_directory}/${file_string}_results_summary.html ${output_directory}/${repertoire_id}.html
    # Add the required label file for the Gateway to present the results as a summary.
    label_file=${output_directory}/${repertoire_id}.txt
    echo "${title_string}" > ${label_file}

    # Remove the intermediate files generated for Conga
    rm -f ${output_directory}/${CONTIG_PREFIX}.csv ${output_directory}/${CONTIG_PREFIX}_*
    rm -f ${output_directory}/features.tsv.gz ${output_directory}/barcodes.tsv.gz ${output_directory}/matrix.mtx.gz ${output_directory}/matrix.mtx.tmp

    # We don't want to keep around the generated data files or the manifest file.
    rm -f ${output_directory}/${cell_file} ${output_directory}/${gex_file} ${output_directory}/${rearrangement_file} ${output_directory}/${manifest_file}

    # done
    printf "IR-INFO: Done running Repertoire Analysis on ${cell_file} at $(date)\n\n"
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
gateway_split_repertoire ${INFO_FILE} ${MANIFEST_FILE} ${ZIP_FILE} ${GATEWAY_ANALYSIS_DIR} "cell_file"
gateway_run_analysis ${INFO_FILE} ${MANIFEST_FILE} ${GATEWAY_ANALYSIS_DIR} "cell_file"
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

