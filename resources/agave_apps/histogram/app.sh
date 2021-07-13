#!/bin/bash

echo "Histogram App 5.0"

##############################################
# init environment
if [ -f /etc/bashrc ]; then
. /etc/bashrc
fi

# Load the environment/modules needed.
module load scipy-stack

# app variables (will be subsituted by AGAVE). If they don't exist
# use command line arguments.
if [ -z "${download_file}" ]; then
	ZIP_FILE=$1
	VARNAME=$2
	NUM_VALUES=$3
else
	ZIP_FILE=${download_file}
	VARNAME=${variable}
	NUM_VALUES=${num_values}
fi
function do_histogram()
# Parameters: VARNAME to process, array of input files
{
    # Temporary files for data
    TMP_FILE=tmp.tsv
    TMP2_FILE=tmp2.tsv
    FINAL_FILE=$TMP_FILE

    # preprocess input files -> tmp.csv
    echo "Extracting $1 from files started at: `date`" 
    echo $1 > $TMP_FILE
    for f in "${tsv_files[@]}"; do
	echo "    Extracting $1 from $f"
	python3 preprocess.py $f $1 >> $TMP_FILE
    done

    if [ $NUM_VALUES .gt 0]
    then
	head --lines=1 $TMP_FILE > $TMP2_FILE
	tail --lines=+2 $TMP_FILE | sort | head --lines=$NUM_VALUES >> $TMP2_FILE
	FINAL_FILE=$TMP2_FILE
    fi
    ##############################################
    # Generate the image file.
    OFILE_BASE="report-$1-histogram"

    # Debugging output
    echo "Histogram started at: `date`" 
    echo "Input file = $FINAL_FILE"
    echo "Variable = $1"
    echo "Output file = $OFILE_BASE.png"

    # Run the python histogram command
    #python histogram.py $TMP_FILE $OFILE
    python3 airr_histogram.py $1 $FINAL_FILE $OFILE_BASE.png
    #convert $OFILE_BASE.png $OFILE_BASE.jpg

    # change permissions
    chmod 644 "$OFILE_BASE.png"
    #chmod 644 "$OFILE_BASE.jpg"

    # Remove the temporary file.
    #rm -f $TMP_FILE
    #rm -f $TMP2_FILE
}

# The Gateway provides information about the download in the file info.txt
INFO_FILE=info.txt

##############################################
# uncompress zip file
#XXXunzip "$ZIP_FILE" && rm "$ZIP_FILE"
echo "Extracting files started at: `date`" 
unzip -o "$ZIP_FILE" 

# Determine the files to process. We extract the .tsv files from the info.txt
tsv_files=( `cat $INFO_FILE | awk -F" " 'BEGIN {count=0} /tsv/ {if (count>0) printf(" %s",$1); else printf("%s", $1); count++}'` )

# Run the historgram for the variable of interest
do_histogram $VARNAME

# Cleanup the input data files, don't want to return them as part of the resulting analysis
echo "Removing original ZIP file $ZIP_FILE"
rm -f $ZIP_FILE
echo "Removing extracted files for each repository"
for f in "${tsv_files[@]}"; do
    echo "    Removing extracted file $f"
    rm -f $f
done


# Debugging output, print data/time when shell command is finished.
echo "Histogram finished at: `date`"

