#!/bin/bash

echo "iReceptor Gateway Utilities"

# Define the directory to use for Gateway analysis output.
export GATEWAY_ANALYSIS_DIR="gateway_analysis"

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
    pushd ${WORKING_DIR} > /dev/null

    # Uncompress zip file
    echo "Extracting files started at: `date`" 
    unzip -o "$ZIP_FILE" 
    echo "Extracting files finished at: `date`" 

    # Go back to where we started
    popd > /dev/null

}

function gateway_split_repertoire(){
# Parameters:
#     $1 - iReceptor info.txt file
#     $2 - AIRR Manifest file
#     $3 - iReceptor ZIP file
#     $4 - Working directory
#     $5 - Type of analysis from the manifest (rearrangement_file, clone_file, cell_file)

    # The Gateway provides information about the download in the file info.txt and
    # an AIRR Manifest JSON file.
    local INFO_FILE=$1
    local MANIFEST_FILE=$2
    # The data, including the info and manifest files, are in the ZIP file.
    local ZIP_FILE=$3
    # We want a working directory for the processing
    local WORKING_DIR=$4
    # The type of analysis to do
    local ANALYSIS_TYPE=""
    if [ -z "$5" ]; then
        ANALYSIS_TYPE="rearrangement_file"
    else
        ANALYSIS_TYPE=$5
    fi


    # Unzip the iReceptor Gateway ZIP file into the working directory
    gateway_unzip ${ZIP_FILE} ${WORKING_DIR}

    # We need a field on which to split the data.
    SPLIT_FIELD="repertoire_id"

    # Move into the working directory to do work...
    pushd ${WORKING_DIR} > /dev/null

    # Determine the files to process. We extract the data files from the airr_manifest.json
    # and store them in an array. The type is one of rearrangement_file, cell_file, clone_file
    data_files=( `python3 ${SCRIPT_DIR}/${GATEWAY_UTIL_DIR}/manifest_summary.py ${MANIFEST_FILE} ${ANALYSIS_TYPE}` )
    if [ $? -ne 0 ]
    then
        echo "IR-ERROR: Could not process manifest file ${MANIFEST_FILE}"
        exit $?
    fi
    echo "Data files = ${data_files[@]}"
    if [ ${#data_files[@]} -eq 0 ]; then
        echo "IR-INFO: Could not find any ${ANALYSIS_TYPE} in ${MANIFEST_FILE}"
    fi

    # Get the repository from the manifest file.
    repository_urls=( `python3 ${SCRIPT_DIR}/${GATEWAY_UTIL_DIR}/manifest_summary.py ${MANIFEST_FILE} "repository"` )
    echo "Repository URLs = ${repository_urls[@]}"

    # Get the Reperotire files from the manifest file.
    repertoire_files=( `python3 ${SCRIPT_DIR}/${GATEWAY_UTIL_DIR}/manifest_summary.py ${MANIFEST_FILE} "repertoire_file"` )
    echo "Repertoire files = ${repertoire_files[@]}"

    # For each repository, process the data from it.
    count=0
    for repository_url in "${repository_urls[@]}"; do
        data_file=${data_files[$count]}
        repertoire_file=${repertoire_files[$count]}
        repository_name=`echo "$repository_url" | awk -F/ '{print $3}'`
        echo ""
        echo "Processing data from repository ${repository_name}"
        echo "Repertoire file = ${repertoire_file}"
        echo "Data file = ${data_file}"

        # Get an array of unique repertoire_ids from the data file
        echo "    Extracting ${SPLIT_FIELD} from $data_file"
        if [ ${ANALYSIS_TYPE} = "rearrangement_file" ]
        then
            # preprocess.py dumps a field of interest from a TSV data file. We want
            # all of the reperotire_ids - we sort them to get unique ids and then
            # use awk to print them all on the same line to create an array
            # of repertoire_ids
            repertoire_ids=( `python3 ${SCRIPT_DIR}/${GATEWAY_UTIL_DIR}/preprocess.py $data_file $SPLIT_FIELD | sort -u | awk '{printf("%s ",$0)}'` )
        elif [ ${ANALYSIS_TYPE} = "cell_file" ]
        then
            # preprocess-json.py dumps a field of interest from a JSON data file. We want
            # all of the reperotire_ids - we sort them to get unique ids and then
            # use awk to print them all on the same line to create an array
            # of repertoire_ids
            repertoire_ids=( `python3 ${SCRIPT_DIR}/${GATEWAY_UTIL_DIR}/preprocess-json.py $data_file Cell $SPLIT_FIELD | sort -u | awk '{printf("%s ",$0)}'` )
        elif [ ${ANALYSIS_TYPE} = "clone_file" ]
        then
            # preprocess-json.py dumps a field of interest from a JSON data file. We want
            # all of the reperotire_ids - we sort them to get unique ids and then
            # use awk to print them all on the same line to create an array
            # of repertoire_ids
            repertoire_ids=( `python3 ${SCRIPT_DIR}/${GATEWAY_UTIL_DIR}/preprocess-json.py $data_file Clone $SPLIT_FIELD | sort -u | awk '{printf("%s ",$0)}'` )
        else
            echo "IR-ERROR: Do not know how to split repertoires for ${ANALYSIS_TYPE}"
            exit 1
        fi

        # Create a directory for each repository (mkdir if it isn't already with -p)
        mkdir -p ${repository_name}
        
        # For each repertoire_id, extract the data in a directory for that repertoire
        for repertoire_id in "${repertoire_ids[@]}"; do
            # Get a directory name and make the directory
            echo "File $data_file has repertoire_id = ${repertoire_id}"
            repertoire_dirname=${repertoire_id}
            mkdir -p ${repository_name}/${repertoire_dirname}

            if [ ${ANALYSIS_TYPE} = "rearrangement_file" ]
            then
                # Generate a file name for the TSV data for the repertoire.
                repertoire_datafile=${repertoire_dirname}".tsv"
    
                # Filter the input file $data_file and extract all records that have the given
                # repertoire_id in the SPLIT_FIELD.
                # Command line parameters: inputfile, field_name, field_value, outfile
                python3 ${SCRIPT_DIR}/${GATEWAY_UTIL_DIR}/filter.py $data_file ${SPLIT_FIELD} ${repertoire_id} ${repository_name}/${repertoire_dirname}/${repertoire_datafile}
        
                # Call the client supplied "run_analysis" callback function. Parameters:
                #     $1 output directory
                #     $2 repository name
                #     $3 repertoire id ("NULL" if not used)
                #     $4 repertoire JSON file ["NULL" if not used, required if repertoire_id is specified]
                #     $5-$N list of data input files
                data_array=( "${repository_name}/${repertoire_dirname}/${repertoire_datafile}" )
                run_analysis ${repository_name}/${repertoire_dirname} ${repository_name} ${repertoire_id} ${repertoire_file} ${data_array[@]} 
            elif [ ${ANALYSIS_TYPE} = "clone_file" ]
            then
                # Generate a file name for the TSV data for the repertoire.
                repertoire_datafile=${repertoire_dirname}".json"
    
                # Filter the input file $data_file and extract all records that have the given
                # repertoire_id in the SPLIT_FIELD.
                # Command line parameters: inputfile, field_name, field_value, outfile
                python3 ${SCRIPT_DIR}/${GATEWAY_UTIL_DIR}/filter-json.py $data_file Clone ${SPLIT_FIELD} ${repertoire_id} > ${repository_name}/${repertoire_dirname}/${repertoire_datafile}
        
                # Call the client supplied "run_analysis" callback function. Parameters:
                #     $1 output directory
                #     $2 repository name
                #     $3 repertoire id ("NULL" if not used)
                #     $4 repertoire JSON file ["NULL" if not used, required if repertoire_id is specified]
                #     $5-$N list of data input files
                data_array=( "${repository_name}/${repertoire_dirname}/${repertoire_datafile}" )
                run_analysis ${repository_name}/${repertoire_dirname} ${repository_name} ${repertoire_id} ${repertoire_file} ${data_array[@]} 
            elif [ ${ANALYSIS_TYPE} = "cell_file" ]
            then
                # Generate a file name for the data for the repertoire.
                repertoire_datafile=${repertoire_dirname}".json"
    
                # Filter the input file $data_file and extract all records that have the given
                # repertoire_id in the SPLIT_FIELD.
                # Command line parameters: inputfile, field_name, field_value, outfile
                python3 ${SCRIPT_DIR}/${GATEWAY_UTIL_DIR}/filter-json.py $data_file Cell ${SPLIT_FIELD} ${repertoire_id} > ${repository_name}/${repertoire_dirname}/${repertoire_datafile}
        
                # Call the client supplied "run_analysis" callback function. Parameters:
                #     $1 output directory
                #     $2 repository name
                #     $3 repertoire id ("NULL" if not used)
                #     $4 repertoire JSON file ["NULL" if not used, required if repertoire_id is specified]
                #     $5-$N list of data input files
                data_array=( "${repository_name}/${repertoire_dirname}/${repertoire_datafile}" )
                run_cell_analysis ${repository_name}/${repertoire_dirname} ${repository_name} ${repertoire_id} ${repertoire_file} ${data_array[@]} 
            fi

        done
        count=$((count+1))
    done

    # Clean up the files we created
    # First the ZIP file
    rm -f ${ZIP_FILE}
    # Remove any TSV files extracted from the ZIP - they are big and can be re-generated
    for f in "${data_files[@]}"; do
        rm -f $f
    done

    # Return to the directory we started from.
    popd > /dev/null

}

# Write a message saying loaded OK
echo "Done loading iReceptor Gateway Utilities"

