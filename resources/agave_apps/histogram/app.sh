#!/bin/bash

echo "Histogram App 5.0"

##############################################
# init environment
if [ -f /etc/bashrc ]; then
. /etc/bashrc
fi

# app variables (will be subsituted by AGAVE)
ZIP_FILE=${file1}
VARNAME=${param1}

##############################################
# uncompress zip file
unzip "$ZIP_FILE" && rm "$ZIP_FILE"

# preprocess input file -> tmp.csv
python preprocess.py data.csv > tmp.csv && rm data.csv

##############################################
# use tmp.csv to generate the histogram
IFILE="tmp.csv"
OFILE="histogram.jpg"

# Debugging output
echo "Histogram started at: `date`" 
echo "Input file = $IFILE"
echo "Variable = $VARNAME"
echo "Output file = $OFILE"

# Generate the matlab command to execute.
COMMAND="histogram('$IFILE','$VARNAME',-1,'Frequency Count','$OFILE')"
echo "Command = $COMMAND"

# Load the module required. We need 2014a for some of the table functions.
module load matlab/2014a

# Run matlab with the command
matlab -nosplash -nodisplay -singleCompThread -r "$COMMAND;exit"

# change permissions
chmod 644 "$OFILE"

# Debugging output, print data/time when shell command is finished.
echo "Histogram finished at: `date`"

rm "$IFILE"
