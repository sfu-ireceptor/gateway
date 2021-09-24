#!/bin/bash

echo "iReceptor Gateway Utilities"

function gateway_split_repertoire(){
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
    cp ${ZIP_FILE} ${WORKING_DIR}

    # Move into the working directory to do work...
    pushd ${WORKING_DIR}

    # Uncompress zip file
    echo "Extracting files started at: `date`" 
    unzip -o "$ZIP_FILE" 

    # Determine the files to process. We extract the .tsv files from the info.txt
    # and store them in an array.
    # TODO: We need to change this to use the AIRR Manifest file.
    tsv_files=( `cat $INFO_FILE | awk -F" " 'BEGIN {count=0} /tsv/ {if (count>0) printf(" %s",$1); else printf("%s", $1); count++}'` )


    # For each TSV file in the array, process it.
    for f in "${tsv_files[@]}"; do
	# Get an array of unique repertoire_ids from the TSV file
        echo "    Extracting ${SPLIT_FIELD} from $f"
        repertoire_ids=( `python3 ${SCRIPT_DIR}/preprocess.py $f $SPLIT_FIELD | sort -u | awk '{printf("%s ",$0)}'` )

	# Create a directory for each repository (mkdir if it isn't already with -p)
        repository_name="${f%.*}"
        mkdir -p ${repository_name}
	# Get the accompanying JSON file for the repository.
        json_file=${repository_name}-metadata.json
        echo "JSON file = ${json_file}"
	
	# For each repertoire_id, extract the data in a directory for that repertoire
        for repertoire_id in "${repertoire_ids[@]}"; do
	    # Get a directory name and make the directory
            echo "File $f has repertoire_id = ${repertoire_id}"
	    repertoire_dirname=${repertoire_id}
	    mkdir -p ${repository_name}/${repertoire_dirname}

	    # Generate a file name for the TSV data for the repertoire.
	    repertoire_tsvfile=${repertoire_dirname}".tsv"
    
	    # Generate some identifier strings for this repertoire. repertoire_summary.py
	    # joins together a bunch of field values for a repertoire that are hopefully
	    # indicative of the sample (subject, sample id, target locus, etc).
	    # We create two, one with fields separated by _ so we can use it in a file name
	    # and the other separated with the default (which is a space).
	    repertoire_string=`python3 ${SCRIPT_DIR}/repertoire_summary.py ${json_file} ${repertoire_id} --separator "_"`
	    repertoire_string=${repository_name}_${repertoire_string// /}
	    title="$(python3 ${SCRIPT_DIR}/repertoire_summary.py ${json_file} ${repertoire_id})"
	    # We want to strip the spaces out of it - bash doesn't like strings with spaces as command line args.
	    # TODO: Fix this, it should not be required.
	    title=${title// /}
	    echo $title
	
            # Filter the input file $f and extract all records that the given repertoire_id in the SPLIT_FIELD.
	    # Command line parameters: inputfile, field_name, field_value, outfile
	    python3 ${SCRIPT_DIR}/filter.py $f ${SPLIT_FIELD} ${repertoire_id} ${repository_name}/${repertoire_dirname}/${repertoire_tsvfile}
	
	    # Call the client supplied "run_repertoire_analysis" function with the following parameters.
            #     $1 input files
            #     $2 output location
            #     $3 graph file string
            #     $4 graph title
	    run_repertoire_analysis ${repository_name}/${repertoire_dirname}/${repertoire_tsvfile} ${repository_name}/${repertoire_dirname} ${repertoire_string} ${title}

        done
    done

    # Clean up the files we created
    # First the ZIP file
    rm -f ${ZIP_FILE}
    # Remove any TSV files extracted from the ZIP - they are big and can be re-generated
    rm -f *.tsv
    # We want to leave the JSON file and INFO.txt files.

    # Return to the directory we started from.
    popd

}

# Write a message saying loaded OK
echo "Done loading iReceptor Gateway Utilities"

