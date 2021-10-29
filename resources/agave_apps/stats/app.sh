#!/bin/bash

echo "iReceptor Stats App"

##############################################
# init environment
if [ -f /etc/bashrc ]; then
. /etc/bashrc
fi

# Get the script directory where all the code is.
SCRIPT_DIR=`pwd`
echo "Running job from ${SCRIPT_DIR}"

# Load the environment/modules needed.
module load scipy-stack

# Get the iRecpetor Gateway utilities from the Gateway
echo "Downloading iReceptor Gateway Utilities from the Gateway"
date
GATEWAY_UTIL_DIR=gateway_utilities
mkdir -p ${GATEWAY_UTIL_DIR}
pushd ${GATEWAY_UTIL_DIR}
wget -r -nH --no-parent --cut-dir=1 --reject="index.html*" --reject="robots.txt*" https://gateway-analysis.ireceptor.org/gateway_utilities/
popd
echo "Done downloading iReceptor Gateway Utilities"
date

# Load the iReceptor Gateway utilities functions.
. ${SCRIPT_DIR}/${GATEWAY_UTIL_DIR}/gateway_utilities.sh

# app variables (will be subsituted by Tapis). If they don't exist
# use command line arguments.
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


function do_heatmap()
#     $1,$2 are variable names to process
#     $3 array of input files
#     $4 output directory 
#     $5 name of processing object (use to tag file)
#     $6 title of processing object (use in title of graph)
{
    # Temporary file for data
    TMP_FILE=tmp.tsv
    # Get the array of files to process
    array_of_files=$3

    # preprocess input files -> tmp.csv
    echo "Extracting $1 and $2 from files started at: `date`" 
    rm -f $TMP_FILE
    echo "$1\t$2" > $TMP_FILE

    for filename in "${array_of_files[@]}"; do
	echo "    Extracting $1 and $2 from $filename"
	# Get the columns numbers for the column labels of interest.
	x_column=`cat $filename | head -n 1 | awk -F"\t" -v label=$1 '{for(i=1;i<=NF;i++){if ($i == label){print i}}}'`
	y_column=`cat $filename | head -n 1 | awk -F"\t" -v label=$2 '{for(i=1;i<=NF;i++){if ($i == label){print i}}}'`
	echo "    Columns = ${x_column}, ${y_column}"

	# Extract the two columns of interest. In this case we want the gene (not including the allele)
	# As a result we chop things off at the first star. This also takes care of the case where
	# a gened call has multiple calls. Since we drop everthing after the first allele we drop all of
	# the other calls as well.
	cat $filename | cut -f $x_column,$y_column | awk -v xlabel=$1 -v ylabel=$2 'BEGIN {FS="\t"; printf("%s\t%s\n", xlabel, ylabel)} /IG|TR/ {if (index($1,"*") == 0) {xstr = $1} else {xstr=substr($1,0,index($1,"*")-1)};if (index($2,"*") == 0) {ystr = $2} else {ystr=substr($2,0,index($2,"*")-1)};printf("%s\t%s\n",xstr,ystr)}' > $TMP_FILE

    done
    # Generate a set of unique values that we can generate the heatmap on. This is a comma separated
    # list of unique gene names for each of the two fields of interest.
    xvals=`cat $TMP_FILE | cut -f 1 | awk 'BEGIN {FS=","} {if (NR>1) print($1)}' | sort | uniq | awk '{if (NR>1) printf(",%s", $1); else printf("%s", $1)}'`
    #yvals=`cat $TMP_FILE | cut -f 2 | awk 'BEGIN {FS=","} /IG/ {print($1)} /TR/ {print($1)}' | sort | uniq | awk '{if (NR>1) printf(",%s", $1); else printf("%s", $1)}'`
    yvals=`cat $TMP_FILE | cut -f 2 | awk 'BEGIN {FS=","} {if (NR>1) print($1)}' | sort | uniq | awk '{if (NR>1) printf(",%s", $1); else printf("%s", $1)}'`

    # Finally we generate a heatmap given all of the processed information.
    echo "$1"
    echo "$2"
    echo "$xvals"
    echo "$yvals"
    PNG_OFILE=$5-$1-$2-heatmap.png
    TSV_OFILE=$5-$1-$2-heatmap.tsv

    # Generate the heatmap
    python3 ${SCRIPT_DIR}/airr_heatmap.py $1 $2 $xvals $yvals $TMP_FILE $PNG_OFILE $TSV_OFILE "$6($1,$2)"

    # change permissions
    chmod 644 "$PNG_OFILE"
    chmod 644 "$TSV_OFILE"

    # Move output file to output directory
    mv $PNG_OFILE $4
    mv $TSV_OFILE $4

    # Remove the temporary file.
    rm -f $TMP_FILE
}

function do_histogram()
# Parameters:
#     $1 is variable_name to process
#     $2 array of input files
#     $3 output directory 
#     $4 name of processing object (use to tag file)
#     $5 title of processing object (use in title of graph)
{
    # Temporary file for data
    TMP_FILE=tmp.tsv
    # Get the array of files to process
    array_of_files=$2

    # preprocess input files -> tmp.csv
    echo ""
    echo "Histogram started at: `date`" 
    echo "Extracting $1 from files started at: `date`" 
    echo $1 > $TMP_FILE
    for filename in "${array_of_files[@]}"; do
	echo "    Extracting $1 from $filename"
	python3 ${SCRIPT_DIR}/${GATEWAY_UTIL_DIR}/preprocess.py $filename $1 >> $TMP_FILE
    done

    ##############################################
    # Generate the image file.
    OFILE_BASE="$4-$1"
    PNG_OFILE=${OFILE_BASE}-histogram.png
    TSV_OFILE=${OFILE_BASE}-histogram.tsv

    # Debugging output
    echo "Input file = $TMP_FILE"
    echo "Variable = $1"
    echo "Graph output file = $PNG_OFILE"
    echo "Data output file = $TSV_OFILE"

    # Run the python histogram command
    python3 ${SCRIPT_DIR}/airr_histogram.py $1 $TMP_FILE $PNG_OFILE $TSV_OFILE "$5,$1"

    # change permissions
    chmod 644 $PNG_OFILE
    chmod 644 $TSV_OFILE

    # Move output file to output directory
    mv $PNG_OFILE $3
    mv $TSV_OFILE $3

    # Remove the temporary file.
    rm -f $TMP_FILE
}

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
	    file_string=`python3 ${SCRIPT_DIR}/${GATEWAY_UTIL_DIR}/repertoire_summary.py ${repertoire_file} ${repertoire_id} --separator "_"`
	    file_string=${repository_name}_${file_string// /}
            title_string="$(python3 ${SCRIPT_DIR}/${GATEWAY_UTIL_DIR}/repertoire_summary.py ${repertoire_file} ${repertoire_id})"
            # TODO: Fix this, it should not be required.
            title_string=${title_string// /}
        else 
	    file_string="total"
	    title_string="Total"
	fi

	# Generate the histogram and heatmap stats
	do_histogram v_call $array_of_files $output_directory $file_string $title_string
        do_histogram d_call $array_of_files $output_directory $file_string $title_string
        do_histogram j_call $array_of_files $output_directory $file_string $title_string
        do_histogram junction_aa_length $array_of_files $output_directory $file_string $title_string
        do_heatmap v_call j_call $array_of_files $output_directory $file_string $title_string
        do_heatmap v_call junction_aa_length $array_of_files $output_directory $file_string $title_string
}

# Set up the required variables. An iReceptor Gateway download consists
# of both an "info.txt" file that describes the download as well as an
# AIRR manifest JSON file that describes the relationships between
# AIRR Repertoire JSON files and AIRR TSV files.
WORKING_DIR="analysis_output"
INFO_FILE="info.txt"
MANIFEST_FILE="airr_manifest.json"

if [ "${split_repertoire}" = "True" ]; then
    # Split the download into single repertoire files, with a directory per
    # repository and within that a directory per repertoire. This expects the 
    # user to define a function called run_analysis() that will be
    # called for each repertoire. See the docs in the gateway_utilities.sh file
    # for parameters to this function.
    gateway_split_repertoire ${INFO_FILE} ${MANIFEST_FILE} ${ZIP_FILE} ${WORKING_DIR}
elif [ "${split_repertoire}" = "False" ]; then
    # Run the stats on all the data combined. Unzip the files
    gateway_unzip ${ZIP_FILE} ${WORKING_DIR}

    # Go into the working directory
    pushd ${WORKING_DIR}

    # Generate the TSV files from the AIRR manifest
    tsv_files=( `python3 ${SCRIPT_DIR}/${GATEWAY_UTIL_DIR}/manifest_summary.py ${MANIFEST_FILE} rearrangement_file` )
    if [ $? -ne 0 ]
    then
        echo "Error: Could not process manifest file ${1}"
        exit $?
    fi
    echo "TSV files = $tsv_files"

    # Run the analysis with a token repository name of "all"
    run_analysis $tsv_files . "all"

    # Remove the extracted TSV files, we don't want to return them
    for f in "${tsv_files[@]}"; do
        rm -f $f
    done
    # Remove the copied ZIP file
    rm -r ${ZIP_FILE}

    popd
else
    echo "ERROR: Unknown repertoire operation ${split_repertoire}"
fi

# Make sure we are back where we started, although the gateway functions should
# not change the working directory that we are in.
cd ${SCRIPT_DIR}

# We want to move the info.txt and the JSON metadata and manifest files to the main
# directory.
mv ${WORKING_DIR}/${INFO_FILE} .
mv ${WORKING_DIR}/${MANIFEST_FILE} .
repertoire_files=( `python3 ${SCRIPT_DIR}/${GATEWAY_UTIL_DIR}/manifest_summary.py ${MANIFEST_FILE} repertoire_file` )

for f in "${repertoire_files[@]}"; do
    mv ${WORKING_DIR}/$f .
done

# ZIP up the analysis results for easy download
zip -r ${WORKING_DIR}.zip ${WORKING_DIR}

# We don't want the iReceptor Utilities to be part of the results.
rm -rf ${GATEWAY_UTIL_DIR}

# Cleanup the input data files, don't want to return them as part of the resulting analysis
echo "Removing original ZIP file $ZIP_FILE"
rm -f $ZIP_FILE

# Debugging output, print data/time when shell command is finished.
echo "Statistics finished at: `date`"

# Handle AGAVE errors - this doesn't seem to have any effect...
#export JOB_ERROR=1
#if [[ $JOB_ERROR -eq 1 ]]; then
#    ${AGAVE_JOB_CALLBACK_FAILURE}
#fi
