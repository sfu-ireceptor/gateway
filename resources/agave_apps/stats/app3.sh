#!/bin/bash
#
# Wrapper script for running app through the iReceptor Gateway.
#

echo "IR-INFO: iReceptor Statistics App - starting at: `date`"

# Get the script directory where all the code is.
SCRIPT_DIR=${_tapisExecSystemExecDir}
echo "IR-INFO: Running job from ${SCRIPT_DIR}"
ls /

#
# Tapis App Parameters: Will be on the singularity command line to
# the App.
#
split_repertoire=$1

# Tapis parameter ir_gateway_url contains the URL of the source gateway. Use
# this to gather iReceptor Gateway specific resources if needed.
#
# Tapis ENV variables expected
#
ZIP_FILE=${download_file}
# Tapis parameter ir_gateway_url contains the URL of the source gateway. Use
# this to gather iReceptor Gateway specific resources if needed.
GATEWAY_URL="${ir_gateway_url}"

##############################################
# Set up Gateway Utilities
##############################################
echo "IR-INFO: Using Gateway ${GATEWAY_URL}"

# Report where we get the Gateway utilities from
GATEWAY_UTIL_DIR=${gateway_util_dir}
echo "IR-INFO: Using iReceptor Gateway Utilities from ${GATEWAY_UTIL_DIR}"

# Load the iReceptor Gateway utilities functions.
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
        echo "IR-ERROR: GATEWAY_ANALYSIS_DIR not defined, gateway_utilities not loaded correctly." >&2
	exit 1
fi
echo "IR-INFO: Done loading iReceptor Gateway Utilities"

# Start
printf "IR-INFO:\n"
printf "IR-INFO: START at $(date)\n"
printf "IR-INFO: PROCS = ${AGAVE_JOB_PROCESSORS_PER_NODE}\n"
printf "IR-INFO: MEM = ${AGAVE_JOB_MEMORY_PER_NODE}\n"
printf "IR-INFO: SLURM JOB ID = ${SLURM_JOB_ID}\n"
printf "IR-INFO: \n"

#########################################################################
# Code to do the analysis
#########################################################################
function do_heatmap()
#     $1,$2 are variable names to process
#     $3 output directory 
#     $4 name of processing object (use to tag file)
#     $5 title of processing object (use in title of graph)
#     $6-$N remaining arguments are files to process.
{
    # Get the local variables to use
    local variable1=$1
    local variable2=$2
    local output_dir=$3
    local file_tag=$4
    local title=$5
    shift
    shift
    shift
    shift
    shift
    # Remaining variable are the files to process
    local array_of_files=( $@ )
    # Temporary file for data
    TMP_FILE=${output_dir}/tmp.tsv

    # preprocess input files -> tmp.csv
    echo "IR-INFO: Extracting ${variable1} and ${variable2} from files started at: `date`" 
    #rm -f $TMP_FILE
    echo -e "${variable1}\t${variable2}" > $TMP_FILE

    for filename in "${array_of_files[@]}"; do
	    echo "IR-INFO:     Extracting ${variable1} and ${variable2} from $filename"
	    # Get the columns numbers for the column labels of interest.
	    x_column=`cat ${output_dir}/$filename | head -n 1 | awk -F"\t" -v label=${variable1} '{for(i=1;i<=NF;i++){if ($i == label){print i}}}'`
	    y_column=`cat ${output_dir}/$filename | head -n 1 | awk -F"\t" -v label=${variable2} '{for(i=1;i<=NF;i++){if ($i == label){print i}}}'`
	    echo "IR-INFO:     Columns = ${x_column}, ${y_column}"

	    # Extract the two columns of interest. In the case of VDJ calls, we want the gene
        # (not including the allele). As a result we chop things off at the first star.
        # This also takes care of the case where a gene call has multiple calls. Since
        # we drop everthing after the first allele we drop all of the other calls as well.
	    cat ${output_dir}/$filename | cut -f $x_column,$y_column | awk -v xlabel=${variable1} -v ylabel=${variable2} 'BEGIN {FS="\t"} /IG|TR/ {if (index($1,"*") == 0) {xstr = $1} else {xstr=substr($1,0,index($1,"*")-1)};if (index($2,"*") == 0) {ystr = $2} else {ystr=substr($2,0,index($2,"*")-1)};printf("%s\t%s\n",xstr,ystr)}' >> $TMP_FILE
    done
    # Generate a set of unique values that we can generate the heatmap on. This is a comma separated
    # list of unique gene names for each of the two fields of interest.
    xvals=`cat $TMP_FILE | cut -f 1 | awk 'BEGIN {FS=","} {if (NR>1) print($1)}' | sort | uniq | awk '{if (NR>1) printf(",%s", $1); else printf("%s", $1)}'`
    yvals=`cat $TMP_FILE | cut -f 2 | awk 'BEGIN {FS=","} {if (NR>1) print($1)}' | sort | uniq | awk '{if (NR>1) printf(",%s", $1); else printf("%s", $1)}'`

    # Finally we generate a heatmap given all of the processed information.
    echo "IR-INFO: ${variable1}"
    echo "IR-INFO: ${variable2}"
    echo "IR-INFO: $xvals"
    echo "IR-INFO: $yvals"
    PNG_OFILE=${output_dir}/${file_tag}-${variable1}-${variable2}-heatmap.png
    TSV_OFILE=${output_dir}/${file_tag}-${variable1}-${variable2}-heatmap.tsv

    # Generate the heatmap
    python3 /ireceptor/airr_heatmap.py ${variable1} ${variable2} $xvals $yvals $TMP_FILE $PNG_OFILE $TSV_OFILE "${title}(${variable1},${variable2})"
    if [ $? -ne 0 ]
    then
        echo "IR-ERROR: Could not generate heatmap for ${variable1},${variable2}"
        # Remove the temporary file.
        #rm -f $TMP_FILE
        return 
    fi

    # change permissions
    chmod 644 "$PNG_OFILE"
    chmod 644 "$TSV_OFILE"

    # Remove the temporary file.
    #rm -f $TMP_FILE
}

function do_histogram()
# Parameters:
#     $1 is variable_name to process
#     $2 output directory 
#     $3 name of processing object (use to tag file)
#     $4 title of processing object (use in title of graph)
#     $5-$N remaining arguments are files to process.
{
    # Get the local variables to use
    local variable_name=$1
    local output_dir=$2
    local file_tag=$3
    local title=$4
    shift
    shift
    shift
    shift
    # Remaining variable are the files to process
    local array_of_files=( $@ )

    # Temporary file for data
    TMP_FILE=${output_dir}/tmp.tsv

    # preprocess input files -> tmp.csv
    echo ""
    echo "IR-INFO: Histogram started at: `date`" 
    echo -n "IR-INFO: Working from directory: "
    pwd
    echo "IR-INFO: Extracting ${variable_name} from files started at: `date`" 
    rm -f $TMP_FILE
    echo ${variable_name} > $TMP_FILE
    for filename in "${array_of_files[@]}"; do
	    echo "IR-INFO:     Extracting ${variable_name} from $filename"
	    python3 ${GATEWAY_UTIL_DIR}/preprocess.py ${output_dir}/$filename ${variable_name} >> $TMP_FILE
    done

    # Generate the image file.
    OFILE_BASE="${file_tag}-${variable_name}"
    PNG_OFILE=${output_dir}/${OFILE_BASE}-histogram.png
    TSV_OFILE=${output_dir}/${OFILE_BASE}-histogram.tsv

    # Debugging output
    echo "IR-INFO: Input file = $TMP_FILE"
    echo "IR-INFO: Variable = ${variable_name}"
    echo "IR-INFO: Graph output file = $PNG_OFILE"
    echo "IR-INFO: Data output file = $TSV_OFILE"

    # Run the python histogram command. We don't want to sort based on value
    # (sort_values = False) and we use a num_values of 0, which denotes all values.
    python3 /ireceptor/airr_histogram.py ${variable_name} $TMP_FILE $PNG_OFILE $TSV_OFILE False 0 "${title},${variable_name}"
    if [ $? -ne 0 ]
    then
        echo "IR-ERROR: Could not generate histogram for ${variable_name}"
        # Remove the temporary file.
        #rm -f $TMP_FILE
        return 
    fi


    # change permissions
    chmod 644 $PNG_OFILE
    chmod 644 $TSV_OFILE

    # Remove the temporary file.
    #rm -f $TMP_FILE
}

function run_analysis()
# Parameters:
#     $1 output directory
#     $2 repository name [string]
#     $3 repertoire_id ("NULL" if should skip repertoire processing)
#     $4 repertoire file (Not used if repertoire_id == NULL)
#     $5 manifest file
{
	# Use local variables - no scope issues please...
	local output_directory=$1
	local repository_name=$2
	local repertoire_id=$3
	local repertoire_file=$4
    local manifest_file=$5
    echo "IR-INFO: Running a Repertoire Analysis with manifest ${manifest_file}"
    echo "IR-INFO:     Working directory = ${output_directory}"
    echo "IR-INFO:     Repository name = ${repository_name}"
    echo "IR-INFO:     Repertoire id = ${repertoire_id}"
    echo "IR-INFO:     Repertoire file = ${repertoire_file}"
    echo "IR-INFO:     Manifest file = ${manifest_file}"
    echo -n "IR-INFO:     Current diretory = "
    pwd

    # Get a list of rearrangement files to process from the manifest.
    local array_of_files=( `python3 ${GATEWAY_UTIL_DIR}/manifest_summary.py ${manifest_file} "rearrangement_file"` )
    if [ $? -ne 0 ]
    then
        echo "IR-ERROR: Could not process manifest file ${manifest_file}"
        return 
    fi
    echo "IR-INFO:     Using files ${array_of_files[@]}"

    # Check to see if we are processing a specific repertoire_id
    if [ "${repertoire_id}" != "Total" ]; then
	    file_string=`python3 ${GATEWAY_UTIL_DIR}/repertoire_summary.py ${repertoire_file} ${repertoire_id} --separator "_"`
        if [ $? -ne 0 ]
        then
            echo "IR-ERROR: Could not generate repertoire summary from ${repertoire_file}"
            return 
        fi
        title_string="$(python3 ${GATEWAY_UTIL_DIR}/repertoire_summary.py ${repertoire_file} ${repertoire_id})"
        if [ $? -ne 0 ]
        then
            echo "IR-ERROR: Could not generate repertoire summary from ${repertoire_file}"
            return
        fi

    else 
	    file_string="${repertoire_id}"
	    title_string="${repertoire_id}"
	fi

    # Clean up special characters in file and title strings.
    file_string=`echo ${repository_name}_${file_string} | sed "s/[!@#$%^&*() :/-]/_/g"`
    # TODO: Fix this, it should not be required.
    title_string=`echo ${title_string} | sed "s/[ ]//g"`

	# Generate the histogram and heatmap stats
	do_histogram v_call $output_directory $file_string $title_string ${array_of_files[@]}
        do_histogram d_call $output_directory $file_string $title_string ${array_of_files[@]}
        do_histogram j_call $output_directory $file_string $title_string ${array_of_files[@]}
        do_histogram junction_aa_length $output_directory $file_string $title_string ${array_of_files[@]}
        do_heatmap v_call j_call $output_directory $file_string $title_string ${array_of_files[@]}
        do_heatmap v_call junction_aa_length $output_directory $file_string $title_string ${array_of_files[@]}
        # Remove the TSV files, we don't want to return them
        for filename in "${array_of_files[@]}"; do
	        echo "IR-INFO: Removing data file $output_directory/$filename"
		#rm -f $output_directory/$filename
	done

	# Generate a label file for the Gateway to use to present this info to the user
	label_file=${output_directory}/${repertoire_id}.txt
	echo "${title_string}" > ${label_file}

	# Generate a summary HTML file for the Gateway to present this info to the user
	html_file=${output_directory}/${repertoire_id}.html

    # Generate the HTML main block
    printf '<!DOCTYPE HTML5>\n' > ${html_file}
    printf '<html lang="en" dir="ltr">' >> ${html_file}

    # Generate a normal looking iReceptor header
    printf '<head>\n' >>  ${html_file}
    cat ${output_directory}/assets/head-template.html >> ${html_file}
    printf "<title>Stats: %s</title>\n" ${title_string} >> ${html_file}
    printf '</head>\n' >>  ${html_file}

    # Generate an iReceptor top bar for the page
    cat ${output_directory}/assets/top-bar-template.html >> ${html_file}

    # Generate a normal looking iReceptor header
    printf '<div class="container job_container">'  >> ${html_file}

	printf "<h2>Stats: %s</h2>\n" ${title_string} >> ${html_file}
	printf "<h2>Analysis</h2>\n" >> ${html_file}
	printf "<h3>V/J gene usage heatmap</h3>\n" >> ${html_file}
	printf '<img src="%s-v_call-j_call-heatmap.png" width="800">' ${file_string} >> ${html_file}
	printf "<h3>V gene/Junction AA Length heatmap</h3>\n" >> ${html_file}
	printf '<img src="%s-v_call-junction_aa_length-heatmap.png" width="800">' ${file_string} >> ${html_file}
	printf "<h3>V Gene usage</h3>\n" >> ${html_file}
	printf '<img src="%s-v_call-histogram.png" width="800">' ${file_string} >> ${html_file}
	printf "<h3>D Gene usage</h3>\n" >> ${html_file}
	printf '<img src="%s-d_call-histogram.png" width="800">' ${file_string} >> ${html_file}
	printf "<h3>J Gene usage</h3>\n" >> ${html_file}
	printf '<img src="%s-j_call-histogram.png" width="800">' ${file_string} >> ${html_file}
	printf "<h3>Junction AA Length</h3>\n" >> ${html_file}
	printf '<img src="%s-junction_aa_length-histogram.png" width="800">' ${file_string} >> ${html_file}
    # End of main div container
    printf '</div>' >> ${html_file}

    # Use the normal iReceptor footer.
    cat ${output_directory}/assets/footer.html >> ${html_file}

    # Generate end body end HTML
    printf '</body>' >> ${html_file}
    printf '</html>' >> ${html_file}

}

# Set up the required variables. An iReceptor Gateway download consists
# of both an "info.txt" file that describes the download as well as an
# AIRR manifest JSON file that describes the relationships between
# AIRR Repertoire JSON files and AIRR TSV files.
INFO_FILE="info.txt"
AIRR_MANIFEST_FILE="AIRR-manifest.json"

if [ "${split_repertoire}" = "True" ]; then
    echo -e "IR-INFO:\nIR-INFO: Splitting data by Repertoire"
    echo "IR-INFO:"
    # Split the download into single repertoire files, with a directory per
    # repository and within that a directory per repertoire. This expects the 
    # user to define a function called run_analysis() that will be
    # called for each repertoire. See the docs in the gateway_utilities.sh file
    # for parameters to this function.
    gateway_split_repertoire ${INFO_FILE} ${AIRR_MANIFEST_FILE} ${ZIP_FILE} ${GATEWAY_ANALYSIS_DIR}
    gateway_run_analysis ${INFO_FILE} ${AIRR_MANIFEST_FILE} ${GATEWAY_ANALYSIS_DIR} 
    gateway_cleanup ${ZIP_FILE} ${AIRR_MANIFEST_FILE} ${GATEWAY_ANALYSIS_DIR}

elif [ "${split_repertoire}" = "False" ]; then
    echo -e "IR-INFO:\nIR-INFO: Running app on entire data set"
    echo "IR-INFO:"

    # Output directory is called "Total"
    # Run the analysis with a token repository name of "ADC" since the
    # analysis is being run on data from the entire ADC.
    # repertoire_id and repository should be "NULL"
    # Lastly, provide the list of TSV files to process. Remember that
    # the array elements are expanded into separate parameters, which
    # the run_analyis function handles.
    outdir="Total"
    
    # Copy the HTML resources for the Apps
    echo "IR-INFO: Copying HTML assets"
    mkdir -p ${GATEWAY_ANALYSIS_DIR}/${outdir}/assets
    cp -r ${GATEWAY_UTIL_DIR}/assets/* ${GATEWAY_ANALYSIS_DIR}/${outdir}/assets
    if [ $? -ne 0 ]
    then
        echo "IR-ERROR: Could not create HTML asset directory"
    fi

    # Run the stats on all the data combined. Unzip the files
    gateway_unzip ${ZIP_FILE} ${GATEWAY_ANALYSIS_DIR}/${outdir}

    # Run the stats analysis.
    run_analysis ${GATEWAY_ANALYSIS_DIR}/${outdir} "AIRRDataCommons" ${outdir} "NULL" ${GATEWAY_ANALYSIS_DIR}/${outdir}/${AIRR_MANIFEST_FILE}

    # Copy the INFO_FILE to the analysis DIR as the Gateway expects it to be there.
    cp ${GATEWAY_ANALYSIS_DIR}/${outdir}/${INFO_FILE} ${GATEWAY_ANALYSIS_DIR}/

else
    echo "IR-ERROR: Unknown repertoire operation ${split_repertoire}" >&2
    exit 1
fi

# Make sure we are back where we started, although the gateway functions should
# not change the working directory that we are in.
cd ${SCRIPT_DIR}

# We want to move the info.txt to the main directory as the Gateway uses it if
# it is available.
cp ${GATEWAY_ANALYSIS_DIR}/${INFO_FILE} .

# We want to keep the job error and output files as part of the analysis output.
cp output/*.out ${GATEWAY_ANALYSIS_DIR}

# ZIP up the analysis results for easy download
zip -r ${GATEWAY_ANALYSIS_DIR}.zip ${GATEWAY_ANALYSIS_DIR}

# We don't want the iReceptor Utilities to be part of the results.
echo "IR-INFO: Removing Gateway utilities"
#rm -rf ${GATEWAY_UTIL_DIR}

# We don't want the analysis files to remain - they are in the ZIP file
echo "IR-INFO: Removing analysis output"
#rm -rf ${GATEWAY_ANALYSIS_DIR}

# Cleanup the input data files, don't want to return them as part of the resulting analysis
echo "IR-INFO: Removing original ZIP file $ZIP_FILE"
#rm -f $ZIP_FILE

# Debugging output, print data/time when shell command is finished.
echo "IR-INFO: Statistics finished at: `date`"

