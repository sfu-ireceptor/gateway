#!/bin/bash

echo "Share Junction App"

##############################################
# init environment
if [ -f /etc/bashrc ]; then
. /etc/bashrc
fi

# Load the environment/modules needed.
module load scipy-stack
source ~/python/agave/bin/activate

# app variables (will be subsituted by AGAVE). If they don't exist
# use command line arguments.
if [ -z "${file1}" ]; then
	ZIP_FILE=$1
else
	ZIP_FILE=${file1}
fi

function do_shared_junction()
# Parameters: VARNAME to process, array of input files
{
    # Temporary file for data
    TMP_FILE=tmp.tsv

    # preprocess input files -> tmp.csv
    echo "Extracting $1 from files started at: `date`" 
    echo $1 > $TMP_FILE
    for f in "${tsv_files[@]}"; do
	echo "    Extracting $1 from $f"
	python preprocess.py $f $1 >> $TMP_FILE
    done

    ##############################################
    # Generate the image file.
    OFILE_BASE="report-$1-histogram"

    # Debugging output
    echo "Histogram started at: `date`" 
    echo "Input file = $TMP_FILE"
    echo "Variable = $1"
    echo "Output file = $OFILE_BASE.png"

    # Run the python histogram command
    #python histogram.py $TMP_FILE $OFILE
    python airr_histogram.py $1 $TMP_FILE $OFILE_BASE.png
    #convert $OFILE_BASE.png $OFILE_BASE.jpg

    # change permissions
    chmod 644 "$OFILE_BASE.png"
    #chmod 644 "$OFILE_BASE.jpg"

    # Remove the temporary file.
    rm -f $TMP_FILE
}

# Temporary DIR
TMP_DIR=./tmp
# The Gateway provides information about the download in the file info.txt
INFO_FILE=info.txt

##############################################
# uncompress zip file
#XXXunzip "$ZIP_FILE" && rm "$ZIP_FILE"
echo "Extracting files started at: `date`" 
unzip -o "$ZIP_FILE" 

# Determine the files to process. We extract the .tsv files from the info.txt
tsv_files=( `cat $INFO_FILE | awk -F":" 'BEGIN {count=0} /tsv/ {if (count>0) printf(" %s",$1); else printf("%s", $1); count++}'` )

mkdir $TMP_DIR 
mv -f $tsv_files $TMP_DIR

# Run the historgram for the variable of interest
python iReceptor_output_subjectcomp.py -seqs_dir $TMP_DIR -output_dir . -pairwise_limit 5 -outfile_tag 5

# Cleanup the input data files, don't want to return them as part of the resulting analysis
echo "Removing original ZIP file $ZIP_FILE"
rm -f $ZIP_FILE
echo "Removing extracted files for each repository"
for f in "${tsv_files[@]}"; do
    echo "    Removing extracted file $f"
    rm -f $TMP_DIR/$f
done
rmdir $TMP_DIR

# Debugging output, print data/time when shell command is finished.
echo "Shared Junction finished at: `date`"

