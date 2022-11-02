#!/bin/bash

echo "Nishanth App 2.0"

##############################################
# init environment
if [ -f /etc/bashrc ]; then
. /etc/bashrc
fi

# app variables (will be subsituted by AGAVE)
ZIP_FILE=${download_file}

##############################################

echo "PART 1"

# uncompress ZIP file
unzip "$ZIP_FILE" && rm "$ZIP_FILE"

echo "PART 2"

# convert CSV file to TSV format
head -n 2 data.csv
python csv_to_tsv.py data.csv data.tsv
head -n 2 data.tsv

echo "PART 3"

# delete CSV file
rm data.csv

echo "PART 4"

# run Perl script on TSV file
perl ./app.pl data.tsv

echo "PART 5"

# delete TSV file
rm data.tsv
