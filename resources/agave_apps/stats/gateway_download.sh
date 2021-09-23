#!/bin/bash

echo "iReceptor Gateway Utilities"

##############################################
# init environment
#if [ -f /etc/bashrc ]; then
#. /etc/bashrc
#fi

# Load the environment/modules needed.
#module load scipy-stack

# Get the script directory where all the code is.
#SCRIPT_DIR=`pwd`
#echo "Running job from ${SCRIPT_DIR}"

# app variables (will be subsituted by AGAVE). If they don't exist
# use command line arguments.
#if [ -z "${download_file}" ]; then
#	ZIP_FILE=$1
#else
#	ZIP_FILE=${download_file}
#fi

function xxdo_heatmap()
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
    echo "$1\t$2" > $TMP_FILE

    for filename in "${array_of_files[@]}"; do
	echo "    Extracting $1 and $2 from $filename"
	# Get the columns numbers for the column labels of interest.
	x_column=`cat $filename | awk -F"\t" -v label=$1 '{for(i=1;i<=NF;i++){if ($i == label){print i}}}'`
	y_column=`cat $filename | awk -F"\t" -v label=$2 '{for(i=1;i<=NF;i++){if ($i == label){print i}}}'`
	echo "    Columns = $x_column, $y_column"

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
    OFILE=$5-$1-$2-heatmap.png

    # Generate the heatmap
    python3 ${SCRIPT_DIR}/airr_heatmap.py $1 $2 $xvals $yvals $TMP_FILE $OFILE "$6($1,$2)"

    # change permissions
    chmod 644 "$OFILE"

    # Move output file to output directory
    mv $OFILE $4

    # Remove the temporary file.
    rm -f $TMP_FILE
}

function xxdo_histogram()
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
    echo "Extracting $1 from files started at: `date`" 
    echo $1 > $TMP_FILE
    for filename in "${array_of_files[@]}"; do
	echo "    Extracting $1 from $filename"
	python3 ${SCRIPT_DIR}/preprocess.py $filename $1 >> $TMP_FILE
    done

    ##############################################
    # Generate the image file.
    OFILE_BASE="$4-$1-histogram"
    OFILE=${OFILE_BASE}.png

    # Debugging output
    echo "Histogram started at: `date`" 
    echo "Input file = $TMP_FILE"
    echo "Variable = $1"
    echo "Output file = $OFILE"

    # Run the python histogram command
    python3 ${SCRIPT_DIR}/airr_histogram.py $1 $TMP_FILE $OFILE "$5,$1"

    # change permissions
    chmod 644 $OFILE

    # Move output file to output directory
    mv $OFILE $3

    # Remove the temporary file.
    rm -f $TMP_FILE
}

function xxrun_repertoire_analysis()
# Parameters:
#     $1 input files
#     $2 output location
#     $3 graph file string
#     $4 graph title
{
	echo "Running a Repertoire Analysis on $1"
	array_of_files=($1)
	do_histogram v_call $array_of_files $2 $3 $4
        do_histogram d_call $array_of_files $2 $3 $4
        do_histogram j_call $array_of_files $2 $3 $4
        do_histogram junction_aa_length $array_of_files $2 $3 $4
        do_heatmap v_call j_call $array_of_files $2 $3 $4
}

function gateway_split(){
# Parameters:
#     $1 - iReceptor info.txt file
#     $2 - AIRR Manifest file
#     $3 - iReceptor ZIP file
#     $4 - Working directory

# The Gateway provides information about the download in the file info.txt and
# an AIRR Manifest JSON file.
INFO_FILE=$1
MANIFEST_FILE=$2
# The data, including the info and manifest files, are in the ZIP file.
ZIP_FILE=$3
# We want a working directory for the processing
WORKING_DIR=$4

# We need a field on which to split the data.
SPLIT_FIELD="repertoire_id"

# Create a working directory for data processing
mkdir -p ${WORKING_DIR}

# Move the ZIP file to the working directory
mv ${ZIP_FILE} ${WORKING_DIR}

# Move into the working directory to do work...
pushd ${WORKING_DIR}

# Uncompress zip file
echo "Extracting files started at: `date`" 
unzip -o "$ZIP_FILE" 

# Determine the files to process. We extract the .tsv files from the info.txt
# and store them in an array.
# TODO: We need to change this to use the AIRR Manifest file.
tsv_files=( `cat $INFO_FILE | awk -F" " 'BEGIN {count=0} /tsv/ {if (count>0) printf(" %s",$1); else printf("%s", $1); count++}'` )


for f in "${tsv_files[@]}"; do
    echo "    Extracting ${SPLIT_FIELD} from $f"
    repertoire_ids=( `python3 ${SCRIPT_DIR}/preprocess.py $f $SPLIT_FIELD | sort -u | awk '{printf("%s ",$0)}'` )
    repository_name="${f%.*}"
    mkdir -p ${repository_name}
    json_file=${repository_name}-metadata.json
    echo "JSON file = ${json_file}"
    for repertoire_id in "${repertoire_ids[@]}"; do
        echo "File $f has repertoire_id = ${repertoire_id}"
	repertoire_dirname=${repertoire_id}
	mkdir -p ${repository_name}/${repertoire_dirname}
	repertoire_tsvfile=${repertoire_dirname}".tsv"

	repertoire_string=`python3 ${SCRIPT_DIR}/repertoire_summary.py ${repository_name}-metadata.json ${repertoire_id} --separator "_"`
	repertoire_string=${repository_name}_${repertoire_string// /}
	title="$(python3 ${SCRIPT_DIR}/repertoire_summary.py ${json_file} ${repertoire_id})"
	title=${title// /}
	echo $title
	
        # filename, field_name, field_value, outfile
	python3 ${SCRIPT_DIR}/filter.py $f ${SPLIT_FIELD} ${repertoire_id} ${repository_name}/${repertoire_dirname}/${repertoire_tsvfile}
	
	
	#run_repertoire_analysis ${repertoire_tsvfile} ${SCRIPT_DIR} ${repertoire_string} ${title}
        #     $1 input files
        #     $2 output location
        #     $3 graph file string
        #     $4 graph title
	run_repertoire_analysis ${repository_name}/${repertoire_dirname}/${repertoire_tsvfile} ${repository_name}/${repertoire_dirname} ${repertoire_string} ${title}

    done
done

# Return to the directory we started from.
popd

}

#cd ${SCRIPT_DIR}

#cp ${WORKING_DIR}/info.txt .
#cp ${WORKING_DIR}/*.json .

# Cleanup the input data files, don't want to return them as part of the resulting analysis
#echo "Removing original ZIP file $ZIP_FILE"
#rm -f $ZIP_FILE
#echo "Removing extracted files for each repository"
#for f in "${tsv_files[@]}"; do
#    echo "    Removing extracted file $f"
#    rm -f $f
#done


# Debugging output, print data/time when shell command is finished.
FOOBAR="42"
echo "Done loading iReceptor Gateway Utilities"

