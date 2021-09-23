#!/bin/bash

echo "iReceptor Gateway Utilities"

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

# Write a message saying loaded OK
echo "Done loading iReceptor Gateway Utilities"

