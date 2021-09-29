#!/bin/bash

echo "iReceptor Gateway Utilities"

function gateway_unzip() {
# Parameters
#   $1 - iReceptor ZIP file
#   $2 - Working directory to unzip into

    # The data, including the info and manifest files, are in the ZIP file.
    local ZIP_FILE=$1

    # We want a working directory for the processing
    local WORKING_DIR=$2

    # Create a working directory for data processing
    mkdir -p ${WORKING_DIR}

    # Move the ZIP file to the working directory
    cp ${ZIP_FILE} ${WORKING_DIR}

    # Move into the working directory to do work...
    pushd ${WORKING_DIR}

    # Uncompress zip file
    echo "Extracting files started at: `date`" 
    unzip -o "$ZIP_FILE" 
    echo "Extracting files finished at: `date`" 

    # Go back to where we started
    popd

}

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
    
    # Unzip the iReceptor Gateway ZIP file into the working directory
    gateway_unzip ${ZIP_FILE} ${WORKING_DIR}

    # We need a field on which to split the data.
    SPLIT_FIELD="repertoire_id"

    # Move into the working directory to do work...
    pushd ${WORKING_DIR}

    # Determine the files to process. We extract the .tsv files from the info.txt
    # and store them in an array.
    tsv_files=( `python3 ${SCRIPT_DIR}/manifest_summary.py ${MANIFEST_FILE} rearrangement_file` )
    if [ $? -ne 0 ]
    then
	echo "Error: Could not process manifest file ${MANIFEST_FILE}"
	exit $?
    fi
    echo "TSV files = $tsv_files"

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
    
            # Filter the input file $f and extract all records that have the given
	    # repertoire_id in the SPLIT_FIELD.
	    # Command line parameters: inputfile, field_name, field_value, outfile
	    python3 ${SCRIPT_DIR}/filter.py $f ${SPLIT_FIELD} ${repertoire_id} ${repository_name}/${repertoire_dirname}/${repertoire_tsvfile}
	
	    # Call the client supplied "run_analysis" callback function. Parameters:
            #     $1 array of TSV input files
            #     $2 output directory
	    #     $3 repository name
            #     $4 repertoire id [optional]
            #     $5 repertoire JSON file [optional - required of repertoire_id is specified]
	    tsv_array=( "${repository_name}/${repertoire_dirname}/${repertoire_tsvfile}" )
	    run_analysis ${tsv_array} ${repository_name}/${repertoire_dirname} ${repository_name} ${repertoire_id} ${json_file}

        done
    done

    # Clean up the files we created
    # First the ZIP file
    rm -f ${ZIP_FILE}
    # Remove any TSV files extracted from the ZIP - they are big and can be re-generated
    # rm -f *.tsv
    # We want to leave the JSON file and INFO.txt files.
    # 

    # Return to the directory we started from.
    popd

}

# Write a message saying loaded OK
echo "Done loading iReceptor Gateway Utilities"

