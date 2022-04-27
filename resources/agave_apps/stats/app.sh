#!/bin/bash

echo "IR-INFO: iReceptor Stats App - starting at: `date`"

##############################################
# init environment
##############################################
if [ -f /etc/bashrc ]; then
. /etc/bashrc
fi

# Get the script directory where all the code is.
SCRIPT_DIR=`pwd`
echo "Running job from ${SCRIPT_DIR}"

# Load the environment/modules needed.
module load scipy-stack

##############################################
# Get the iRecpetor Gateway utilities from the Gateway
##############################################
echo "Downloading iReceptor Gateway Utilities from the Gateway"
date
GATEWAY_UTIL_DIR="gateway_utilities"
mkdir -p ${GATEWAY_UTIL_DIR}
pushd ${GATEWAY_UTIL_DIR} > /dev/null
wget --no-verbose -r -nH --no-parent --cut-dir=1 --reject="index.html*" --reject="robots.txt*" https://gateway-analysis.ireceptor.org/gateway_utilities/
popd > /dev/null
echo "Done downloading iReceptor Gateway Utilities"
date

# Load the iReceptor Gateway utilities functions.
. ${SCRIPT_DIR}/${GATEWAY_UTIL_DIR}/gateway_utilities.sh
# This directory is defined in the gateway_utilities.sh. The Gateway
# relies on this being set. If it isn't set, abort as something has
# gone wrong with loading the Gateway utilties.
echo "Gateway analysis directory = ${GATEWAY_ANALYSIS_DIR}"
if [ -z "${GATEWAY_ANALYSIS_DIR}" ]; then
        echo "IR-ERROR: GATEWAY_ANALYSIS_DIR not defined, gateway_utilities not loaded correctly." >&2
	exit 1
fi
echo "IR-INFO: Done loading iReceptor Gateway Utilities"

#########################################################################
# Application variables (will be subsituted by Tapis). If they don't exist
# use command line arguments.
#########################################################################

# Download file provide by Tapis, if not, set it to command line $1
if [ -z "${download_file}" ]; then
	ZIP_FILE=$1
else
	ZIP_FILE=${download_file}
fi

# If split_repertoire is not provided by Tapis then set it to the 
# command line argument.
echo "Tapis split = ${split_repertoire}"
if [ -z "${split_repertoire}" ]; then
	split_repertoire=$2
fi


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
    TMP_FILE=tmp.tsv

    # preprocess input files -> tmp.csv
    echo "Extracting ${variable1} and ${variable2} from files started at: `date`" 
    rm -f $TMP_FILE
    echo "${variable1}\t${variable2}" > $TMP_FILE

    for filename in "${array_of_files[@]}"; do
	    echo "    Extracting ${variable1} and ${variable2} from $filename"
	    # Get the columns numbers for the column labels of interest.
	    x_column=`cat $filename | head -n 1 | awk -F"\t" -v label=${variable1} '{for(i=1;i<=NF;i++){if ($i == label){print i}}}'`
	    y_column=`cat $filename | head -n 1 | awk -F"\t" -v label=${variable2} '{for(i=1;i<=NF;i++){if ($i == label){print i}}}'`
	    echo "    Columns = ${x_column}, ${y_column}"

	    # Extract the two columns of interest. In this case we want the gene (not including the allele)
	    # As a result we chop things off at the first star. This also takes care of the case where
	    # a gened call has multiple calls. Since we drop everthing after the first allele we drop all of
	    # the other calls as well.
	    cat $filename | cut -f $x_column,$y_column | awk -v xlabel=${variable1} -v ylabel=${variable2} 'BEGIN {FS="\t"; printf("%s\t%s\n", xlabel, ylabel)} /IG|TR/ {if (index($1,"*") == 0) {xstr = $1} else {xstr=substr($1,0,index($1,"*")-1)};if (index($2,"*") == 0) {ystr = $2} else {ystr=substr($2,0,index($2,"*")-1)};printf("%s\t%s\n",xstr,ystr)}' > $TMP_FILE

    done
    # Generate a set of unique values that we can generate the heatmap on. This is a comma separated
    # list of unique gene names for each of the two fields of interest.
    xvals=`cat $TMP_FILE | cut -f 1 | awk 'BEGIN {FS=","} {if (NR>1) print($1)}' | sort | uniq | awk '{if (NR>1) printf(",%s", $1); else printf("%s", $1)}'`
    #yvals=`cat $TMP_FILE | cut -f 2 | awk 'BEGIN {FS=","} /IG/ {print($1)} /TR/ {print($1)}' | sort | uniq | awk '{if (NR>1) printf(",%s", $1); else printf("%s", $1)}'`
    yvals=`cat $TMP_FILE | cut -f 2 | awk 'BEGIN {FS=","} {if (NR>1) print($1)}' | sort | uniq | awk '{if (NR>1) printf(",%s", $1); else printf("%s", $1)}'`

    # Finally we generate a heatmap given all of the processed information.
    echo "${variable1}"
    echo "${variable2}"
    echo "$xvals"
    echo "$yvals"
    PNG_OFILE=${file_tag}-${variable1}-${variable2}-heatmap.png
    TSV_OFILE=${file_tag}-${variable1}-${variable2}-heatmap.tsv

    # Generate the heatmap
    python3 ${SCRIPT_DIR}/airr_heatmap.py ${variable1} ${variable2} $xvals $yvals $TMP_FILE $PNG_OFILE $TSV_OFILE "${title}(${variable1},${variable2})"

    # change permissions
    chmod 644 "$PNG_OFILE"
    chmod 644 "$TSV_OFILE"

    # Move output file to output directory
    mv $PNG_OFILE ${output_dir}
    mv $TSV_OFILE ${output_dir}

    # Remove the temporary file.
    rm -f $TMP_FILE
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
    TMP_FILE=tmp.tsv

    # preprocess input files -> tmp.csv
    echo ""
    echo "Histogram started at: `date`" 
    echo -n "Working from directory: "
    pwd
    echo "Extracting ${variable_name} from files started at: `date`" 
    echo ${variable_name} > $TMP_FILE
    for filename in "${array_of_files[@]}"; do
	    echo "    Extracting ${variable_name} from $filename"
	    python3 ${SCRIPT_DIR}/${GATEWAY_UTIL_DIR}/preprocess.py $filename ${variable_name} >> $TMP_FILE
    done

    ##############################################
    # Generate the image file.
    OFILE_BASE="${file_tag}-${variable_name}"
    PNG_OFILE=${OFILE_BASE}-histogram.png
    TSV_OFILE=${OFILE_BASE}-histogram.tsv

    # Debugging output
    echo "Input file = $TMP_FILE"
    echo "Variable = ${variable_name}"
    echo "Graph output file = $PNG_OFILE"
    echo "Data output file = $TSV_OFILE"

    # Run the python histogram command
    python3 ${SCRIPT_DIR}/airr_histogram.py ${variable_name} $TMP_FILE $PNG_OFILE $TSV_OFILE "${title},${variable_name}"

    # change permissions
    chmod 644 $PNG_OFILE
    chmod 644 $TSV_OFILE

    # Move output file to output directory
    mv $PNG_OFILE ${output_dir}
    mv $TSV_OFILE ${output_dir}

    # Remove the temporary file.
    rm -f $TMP_FILE
}

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

	echo "Running a Repertoire Analysis on ${array_of_files[@]}"

	# Check to see if we are processing a specific repertoire_id
	if [ "${repertoire_id}" != "${output_directory}" ]; then
	    file_string=`python3 ${SCRIPT_DIR}/${GATEWAY_UTIL_DIR}/repertoire_summary.py ${repertoire_file} ${repertoire_id} --separator "_"`
	    file_string=${repository_name}_${file_string// /}
        title_string="$(python3 ${SCRIPT_DIR}/${GATEWAY_UTIL_DIR}/repertoire_summary.py ${repertoire_file} ${repertoire_id})"
        # TODO: Fix this, it should not be required.
        title_string=${title_string// /}
    else 
	    file_string="Total"
	    title_string="Total"
	fi

	# Generate the histogram and heatmap stats
	do_histogram v_call $output_directory $file_string $title_string ${array_of_files[@]}
    do_histogram d_call $output_directory $file_string $title_string ${array_of_files[@]}
    do_histogram j_call $output_directory $file_string $title_string ${array_of_files[@]}
    do_histogram junction_aa_length $output_directory $file_string $title_string ${array_of_files[@]}
    do_heatmap v_call j_call $output_directory $file_string $title_string ${array_of_files[@]}
    do_heatmap v_call junction_aa_length $output_directory $file_string $title_string ${array_of_files[@]}
    # Remove the TSV files, we don't want to return them
    for filename in "${array_of_files[@]}"; do
		rm -f $filename
	done

	# Generate a label file for the Gateway to use to present this info to the user
	label_file=${output_directory}/${repertoire_id}.txt
	echo "${repository_name}: ${title_string}" > ${label_file}

	# Generate a summary HTML file for the Gateway to present this info to the user
	html_file=${output_directory}/${repertoire_id}.html
	printf "<h1>iReceptor Stats Analysis</h1>\n" > ${html_file}
	printf "<h2>Data Summary</h2>\n" >> ${html_file}
	cat info.txt >> ${html_file}
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
}

# Set up the required variables. An iReceptor Gateway download consists
# of both an "info.txt" file that describes the download as well as an
# AIRR manifest JSON file that describes the relationships between
# AIRR Repertoire JSON files and AIRR TSV files.
INFO_FILE="info.txt"
MANIFEST_FILE="airr_manifest.json"

if [ "${split_repertoire}" = "True" ]; then
    echo -e "\nIR-INFO: Splitting data by Repertoire\n"
    # Split the download into single repertoire files, with a directory per
    # repository and within that a directory per repertoire. This expects the 
    # user to define a function called run_analysis() that will be
    # called for each repertoire. See the docs in the gateway_utilities.sh file
    # for parameters to this function.
    gateway_split_repertoire ${INFO_FILE} ${MANIFEST_FILE} ${ZIP_FILE} ${GATEWAY_ANALYSIS_DIR}
elif [ "${split_repertoire}" = "False" ]; then
    echo -e "\nIR-INFO: Running app on entire data set\n"
    # Run the stats on all the data combined. Unzip the files
    gateway_unzip ${ZIP_FILE} ${GATEWAY_ANALYSIS_DIR}

    # Go into the working directory
    pushd ${GATEWAY_ANALYSIS_DIR} > /dev/null

    # Generate the TSV files from the AIRR manifest
    tsv_files=( "`python3 ${SCRIPT_DIR}/${GATEWAY_UTIL_DIR}/manifest_summary.py ${MANIFEST_FILE} rearrangement_file`" )
    if [ $? -ne 0 ]
    then
        echo "IR-ERROR: Could not process manifest file ${MANIFEST_FILE}"
        exit $?
    fi
    echo "TSV files = ${tsv_files}"

    # Output directory is called "Total"
    # Run the analysis with a token repository name of "ADC" since the
    # analysis is being run on data from the entire ADC.
    # repertoire_id and repository should be "NULL"
    # Lastly, provide the list of TSV files to process. Remember that
    # the array elements are expanded into separate parameters, which
    # the run_analyis function handles.
    outdir="Total"
    mkdir ${outdir}
    run_analysis ${outdir} "AIRRDataCommons" ${outdir} "NULL" ${tsv_files[@]} 

    # Remove the copied ZIP file
    rm -r ${ZIP_FILE}

    popd > /dev/null
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
cp *.err ${GATEWAY_ANALYSIS_DIR}
cp *.out ${GATEWAY_ANALYSIS_DIR}

# ZIP up the analysis results for easy download
zip -r ${GATEWAY_ANALYSIS_DIR}.zip ${GATEWAY_ANALYSIS_DIR}

# We don't want the iReceptor Utilities to be part of the results.
echo "Removing Gateway utilities"
rm -rf ${GATEWAY_UTIL_DIR}

# We don't want the analysis files to remain - they are in the ZIP file
echo "Removing analysis output"
rm -rf ${GATEWAY_ANALYSIS_DIR}

# Cleanup the input data files, don't want to return them as part of the resulting analysis
echo "Removing original ZIP file $ZIP_FILE"
rm -f $ZIP_FILE

# Debugging output, print data/time when shell command is finished.
echo "IR-INFO: Statistics finished at: `date`"

# Handle AGAVE errors - this doesn't seem to have any effect...
#export JOB_ERROR=1
#if [[ $JOB_ERROR -eq 1 ]]; then
#    ${AGAVE_JOB_CALLBACK_FAILURE}
#fi
