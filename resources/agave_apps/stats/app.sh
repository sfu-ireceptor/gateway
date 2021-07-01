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
else
	ZIP_FILE=${download_file}
	VARNAME=${variable}
	VARNAME=junction_length
fi

function do_heatmap()
{
    # Temporary file for data
    TMP_FILE=tmp.tsv

    # preprocess input files -> tmp.csv
    echo "Extracting $1 and $2 from files started at: `date`" 
    echo "$1\t$2" > $TMP_FILE

    for f in "${tsv_files[@]}"; do
	echo "    Extracting $1 and $2 from $f"
	# Get the columns numbers for the column labels of interest.
	x_column=`cat $f | awk -F"\t" -v label=$1 '{for(i=1;i<=NF;i++){if ($i == label){print i}}}'`
	y_column=`cat $f | awk -F"\t" -v label=$2 '{for(i=1;i<=NF;i++){if ($i == label){print i}}}'`
	echo "    Columns = $x_column, $y_column"

	# Extract the two columns of interest. In this case we want the gene (not including the allele)
	# As a result we chop things off at the first star. This also takes care of the case where
	# a gened call has multiple calls. Since we drop everthing after the first allele we drop all of
	# the other calls as well.
	cat $f | cut -f $x_column,$y_column | awk -v xlabel=$1 -v ylabel=$2 'BEGIN {FS="\t"; printf("%s\t%s\n", xlabel, ylabel)} /IG|TR/ {printf("%s\t%s\n",substr($1,0,index($1,"*")-1), substr($2,0,index($2,"*")-1))}' > $TMP_FILE

	#python preprocess.py $f $1 >> $TMP_FILE
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
    OFILE=report-$1-$2-heatmap.png

    # Generate the heatmap
    python3 airr_heatmap.py $1 $2 $xvals $yvals $TMP_FILE $OFILE

    # change permissions
    chmod 644 "$OFILE"

    # Remove the temporary file.
    rm -f $TMP_FILE
}

function do_histogram()
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

# The Gateway provides information about the download in the file info.txt
INFO_FILE=info.txt

##############################################
# uncompress zip file
#XXXunzip "$ZIP_FILE" && rm "$ZIP_FILE"
echo "Extracting files started at: `date`" 
unzip -o "$ZIP_FILE" 

# Determine the files to process. We extract the .tsv files from the info.txt
tsv_files=( `cat $INFO_FILE | awk -F":" 'BEGIN {count=0} /tsv/ {if (count>0) printf(" %s",$1); else printf("%s", $1); count++}'` )

# Run the stats for each of the VDJ calls.
do_histogram v_call
do_histogram d_call
do_histogram j_call
do_histogram junction_length
do_heatmap v_call j_call

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

