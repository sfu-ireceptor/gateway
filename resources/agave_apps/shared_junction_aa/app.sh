#!/bin/bash

echo "Shared Junction App"

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
if [ -z "${download_file}" ]; then
	ZIP_FILE="$1"
else
	ZIP_FILE="${download_file}"
fi

# Temporary DIR
TMP_DIR="./tmp"
# The Gateway provides information about the download in the file info.txt
INFO_FILE="info.txt"

##############################################
# uncompress zip file
echo "`date` - extracting files from $ZIP_FILE"
unzip -o "$ZIP_FILE" 

# Determine the files to process. We extract the .tsv files from the info.txt
tsv_files=( `cat $INFO_FILE | awk -F":" 'BEGIN {count=0} /tsv/ {if (count>0) printf(" %s",$1); else printf("%s", $1); count++}'` )

mkdir "$TMP_DIR"
mv -f $tsv_files "$TMP_DIR"

# Run the historgram for the variable of interest
python iReceptor_output_subjectcomp.py -seqs_dir $TMP_DIR -output_dir . -pairwise_limit 5 -outfile_tag 5

# Cleanup the input data files, don't want to return them as part of the resulting analysis
echo "Removing original ZIP file $ZIP_FILE"
rm -f "$ZIP_FILE"
echo "Removing extracted files for each repository"
for f in "${tsv_files[@]}"; do
    echo "    Removing extracted file $f"
    rm -f "$TMP_DIR"/"$f"
done
rmdir "$TMP_DIR"

# Debugging output, print data/time when shell command is finished.
echo "Shared Junction finished at: `date`"

