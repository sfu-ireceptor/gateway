#!/bin/bash

echo "iReceptor Stats App"

##############################################
# init environment
if [ -f /etc/bashrc ]; then
. /etc/bashrc
fi

# Load the environment/modules needed.
module load scipy-stack

# Get the script directory where all the code is.
SCRIPT_DIR=`pwd`
echo "Running job from ${SCRIPT_DIR}"

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
    xvals=`cat $TMP_FILE | cut -f 1 | awk 'BEGIN {FS=","} /IG/ {print($1)} /TR/ {print($1)}' | sort | uniq | awk '{if (NR>1) printf(",%s", $1); else printf("%s", $1)}'`
    yvals=`cat $TMP_FILE | cut -f 2 | awk 'BEGIN {FS=","} /IG/ {print($1)} /TR/ {print($1)}' | sort | uniq | awk '{if (NR>1) printf(",%s", $1); else printf("%s", $1)}'`

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
	python3 ${SCRIPT_DIR}/preprocess.py $filename $1 >> $TMP_FILE
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

function run_full_analysis()
# Parameters:
#     $1 manifest file
#     $2 working directory
{
	# Generate the array of TSV files to process.
	echo "Running Stats analysis on $1"
	local tsv_files=( `python3 ${SCRIPT_DIR}/manifest_summary.py ${1} rearrangement_file` )
	if [ $? -ne 0 ]
        then
	    echo "Error: Could not process manifest file ${1}"
            exit $?
        fi
        echo "TSV files = $tsv_files"

	do_histogram v_call $tsv_files $2 v_call v_call
        do_histogram d_call $tsv_files $2 d_call d_call
        do_histogram j_call $tsv_files $2 j_call j_call
        do_histogram junction_aa_length $tsv_files $2 junction_aa_length junction_aa_length
        do_heatmap v_call j_call $tsv_files $2 "v_call-j_call" "v_call-j_call" 

	# Remove the data file, we don't want to return it as part of 
	# the analysis.
	#echo "Removing generated TSV file $1"
	#rm -f $1
}

function run_repertoire_analysis()
# Parameters:
#     $1 input file
#     $2 output location
#     $3 graph file string
#     $4 graph title
{
	echo "Running a Repertoire Analysis on $1"
	# The graphing functions handle an array of files. This function
	# takes a single file, so we need to create an array of length 1.
	array_of_files=($1)
	do_histogram v_call $array_of_files $2 $3 $4
        do_histogram d_call $array_of_files $2 $3 $4
        do_histogram j_call $array_of_files $2 $3 $4
        do_histogram junction_aa_length $array_of_files $2 $3 $4
        do_heatmap v_call j_call $array_of_files $2 $3 $4

	# Remove the data file, we don't want to return it as part of 
	# the analysis.
	echo "Removing generated TSV file $1"
	rm -f $1
}

# Load the iReceptor Gateway utilities functions.
. ${SCRIPT_DIR}/gateway_utilities.sh

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
    # user to define a function called run_repertoire_analysis() that will be
    # called for each repertoire. See the docs in the gateway_utilities.sh file
    # for parameters to this function.
    gateway_split_repertoire ${INFO_FILE} ${MANIFEST_FILE} ${ZIP_FILE} ${WORKING_DIR}
elif [ "${split_repertoire}" = "False" ]; then
    # Run the stats on all the data combined. Unzip and then do the stats.
    gateway_unzip ${ZIP_FILE} ${WORKING_DIR}
    pushd ${WORKING_DIR}
    run_full_analysis ${MANIFEST_FILE} .
    popd
else
    echo "ERROR: Unknown repertoire operation ${split_repertoire}"
fi

# Make sure we are back where we started, although the gateway functions should
# not change the working directory that we are in.
cd ${SCRIPT_DIR}

# We want to move the info.txt and the JSON metadata and manifest files to the main
# directory.
mv ${WORKING_DIR}/info.txt .
mv ${WORKING_DIR}/*.json .

# Cleanup the input data files, don't want to return them as part of the resulting analysis
echo "Removing original ZIP file $ZIP_FILE"
rm -f $ZIP_FILE

# Debugging output, print data/time when shell command is finished.
echo "Statistics finished at: `date`"

