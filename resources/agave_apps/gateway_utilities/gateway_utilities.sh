#!/bin/bash

echo "GW-INFO: iReceptor Gateway Utilities"

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
    echo "GW-INFO: Extracting files into ${WORKING_DIR} started at: `date`" 
    unzip -o "$ZIP_FILE" 
    echo "GW-INFO: Extracting files finished at: `date`" 

    # Remove the copied ZIP file.
    rm -f ${ZIP_FILE} 

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

    echo "GW-INFO: Splitting AIRR Repertoires"
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
    echo "GW-INFO: Analysis type = ${ANALYSIS_TYPE}"

    # Unzip the iReceptor Gateway ZIP file into the working directory
    gateway_unzip ${ZIP_FILE} ${WORKING_DIR}

    # We need a field on which to split the data.
    SPLIT_FIELD="repertoire_id"
    LINK_FIELD="data_processing_id"

    # Move into the working directory to do work...
    pushd ${WORKING_DIR} > /dev/null

    # Determine the files to process. We extract the data files from the AIRR-manifest.json
    # and store them in an array. The type is one of rearrangement_file, cell_file, clone_file
    if [ ${ANALYSIS_TYPE} = "rearrangement_file" ]
    then
        data_files=( `python3 ${SCRIPT_DIR}/${GATEWAY_UTIL_DIR}/manifest_summary.py ${MANIFEST_FILE} ${ANALYSIS_TYPE}` )
    elif [ ${ANALYSIS_TYPE} = "clone_file" ]
    then
        data_files=( `python3 ${SCRIPT_DIR}/${GATEWAY_UTIL_DIR}/manifest_summary.py ${MANIFEST_FILE} ${ANALYSIS_TYPE}` )
    elif [ ${ANALYSIS_TYPE} = "cell_file" ]
    then
        # Cell analyses have three different types of files to process.
        data_files=( `python3 ${SCRIPT_DIR}/${GATEWAY_UTIL_DIR}/manifest_summary.py ${MANIFEST_FILE} ${ANALYSIS_TYPE}` )
        expression_files=( `python3 ${SCRIPT_DIR}/${GATEWAY_UTIL_DIR}/manifest_summary.py ${MANIFEST_FILE} "expression_file"` )
        rearrangement_files=( `python3 ${SCRIPT_DIR}/${GATEWAY_UTIL_DIR}/manifest_summary.py ${MANIFEST_FILE} "rearrangement_file"` )
    fi

    # Check to make sure we have some data files to process in the manifest file.
    echo "GW-INFO: Data files = ${data_files[@]}"
    if [ ${#data_files[@]} -eq 0 ]; then
        echo "GW-ERROR: Could not find any ${ANALYSIS_TYPE} in ${MANIFEST_FILE}"
        exit $?
    fi

    # Get the repository from the manifest file.
    repository_urls=( `python3 ${SCRIPT_DIR}/${GATEWAY_UTIL_DIR}/manifest_summary.py ${MANIFEST_FILE} "repository_url"` )
    echo "GW-INFO: Repository URLs = ${repository_urls[@]}"

    # Get the Reperotire files from the manifest file.
    repertoire_files=( `python3 ${SCRIPT_DIR}/${GATEWAY_UTIL_DIR}/manifest_summary.py ${MANIFEST_FILE} "repertoire_file"` )
    echo "GW-INFO: Repertoire files = ${repertoire_files[@]}"

    # For each repository, process the data from it.
    count=0
    for repository_url in "${repository_urls[@]}"; do
        # Get the files to process for each repository. This assumes that there is
        # one data file and repertoire file  per repository
        data_file=${data_files[$count]}
        repertoire_file=${repertoire_files[$count]}
        # Get the repository name (FQDN) of the repository
        repository_name=`echo "$repository_url" | awk -F/ '{print $3}'`
        echo "GW-INFO:"
        echo "GW-INFO: Processing data from repository ${repository_name}"
        echo "GW-INFO: Repertoire file = ${repertoire_file}"
        echo "GW-INFO: Data file = ${data_file}"

        # Get an array of unique repertoire_ids from the data file
        echo "GW-INFO:     Extracting ${SPLIT_FIELD} from $data_file"
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
            echo "GW-ERROR: Do not know how to split repertoires for ${ANALYSIS_TYPE}"
            exit 1
        fi

        # Create a directory for each repository (mkdir if it isn't already with -p)
        mkdir -p ${repository_name}
        
        repertoire_count=0
        # For each repertoire_id, extract the data in a directory for that repertoire
        for repertoire_id in "${repertoire_ids[@]}"; do
            # Get a directory name and make the directory
            echo "GW-INFO: File $data_file has repertoire_id = ${repertoire_id}"
            repertoire_dirname=${repertoire_id}
            mkdir -p ${repository_name}/${repertoire_dirname}

            # Generate the manifest file name for this analysis unit
            REPERTOIRE_MANIFEST=${repository_name}/${repertoire_dirname}/manifest.json
            echo "GW-INFO: Manifest file = ${REPERTOIRE_MANIFEST}"

            # Based on the type of analysis, split the data out for this reperotire_id
            if [ ${ANALYSIS_TYPE} = "rearrangement_file" ]
            then
                # Generate a file name for the TSV data for the repertoire.
                repertoire_datafile=${repertoire_dirname}".tsv"
    
                # Filter the input file $data_file and extract all records that have the given
                # repertoire_id in the SPLIT_FIELD.
                # Command line parameters: inputfile, field_name, field_value, outfile
                python3 ${SCRIPT_DIR}/${GATEWAY_UTIL_DIR}/filter.py $data_file ${SPLIT_FIELD} ${repertoire_id} ${repository_name}/${repertoire_dirname}/${repertoire_datafile}
                if [ $? -ne 0 ]
                then
                    echo "GW-ERROR: Could not filter Rearrangement data for ${repertoire_id} from ${data_file}"
                    continue
                fi
        
                # Create the repertoire manifest file
                echo '{"Info":{},"DataSets":[' > $REPERTOIRE_MANIFEST
                echo "{\"rearrangement_file\":[\"${repertoire_datafile}\"]}" >> $REPERTOIRE_MANIFEST
                echo "]}" >> $REPERTOIRE_MANIFEST

                # Call the client supplied "run_analysis" callback function. Parameters:
                #     $1 output directory
                #     $2 repository name
                #     $3 repertoire id ("NULL" if not used)
                #     $4 repertoire JSON file ["NULL" if not used, required if repertoire_id is specified]
                #     $5 manifest file
                echo "Inputs"
                echo ${repository_name}/${repertoire_dirname}
                echo ${repository_name}
                echo ${repertoire_id}
                echo ${repertoire_file}
                echo ${REPERTOIRE_MANIFEST}
                run_analysis ${repository_name}/${repertoire_dirname} ${repository_name} ${repertoire_id} ${repertoire_file} ${REPERTOIRE_MANIFEST}
            elif [ ${ANALYSIS_TYPE} = "clone_file" ]
            then
                # Generate a file name for the TSV data for the repertoire.
                repertoire_datafile=${repertoire_dirname}".json"
    
                # Filter the input file $data_file and extract all records that have the given
                # repertoire_id in the SPLIT_FIELD.
                # Command line parameters: inputfile, field_name, field_value, outfile
                python3 ${SCRIPT_DIR}/${GATEWAY_UTIL_DIR}/filter-json.py $data_file Clone ${SPLIT_FIELD} ${repertoire_id} > ${repository_name}/${repertoire_dirname}/${repertoire_datafile}
                if [ $? -ne 0 ]
                then
                    echo "GW-ERROR: Could not filter Clone data for ${repertoire_id} from ${data_file}"
                    continue
                fi
        
                # Create the repertoire manifest file
                echo '{"Info":{},"DataSets":[' > $REPERTOIRE_MANIFEST
                echo "{\"clone_file\":[\"${repertoire_datafile}\"]}" >> $REPERTOIRE_MANIFEST
                echo "]}" >> $REPERTOIRE_MANIFEST

                # Call the client supplied "run_analysis" callback function. Parameters:
                #     $1 output directory
                #     $2 repository name
                #     $3 repertoire id ("NULL" if not used)
                #     $4 repertoire JSON file ["NULL" if not used, required if repertoire_id is specified]
                #     $5 manifest file
                run_analysis ${repository_name}/${repertoire_dirname} ${repository_name} ${repertoire_id} ${repertoire_file} ${REPERTOIRE_MANIFEST}
            elif [ ${ANALYSIS_TYPE} = "cell_file" ]
            then
                # Get the expression and rearrangement files that accompany this analysis unit.
                echo "GW-INFO: Expression files = ${expression_files[@]}"
                echo "GW-INFO: Rearrangement files = ${rearrangement_files[@]}"
                expression_file=${expression_files[$count]}
                rearrangement_file=${rearrangement_files[$count]}
                # Generate a file name for the data for the repertoire.
                cell_datafile=${repertoire_dirname}"-cell.json"
                gex_datafile=${repertoire_dirname}"-gex.json"
                rearrangement_datafile=${repertoire_dirname}"-rearrangement.tsv"
    
                # Filter the input file extract all records that have the given
                # repertoire_id in the SPLIT_FIELD.
                # Command line parameters: inputfile, field_name, field_value, outfile
                echo "GW-INFO: Splitting Cell file ${data_file} by ${SPLIT_FIELD} ${repertoire_id}"
                python3 ${SCRIPT_DIR}/${GATEWAY_UTIL_DIR}/filter-json.py $data_file Cell ${SPLIT_FIELD} ${repertoire_id} > ${repository_name}/${repertoire_dirname}/${cell_datafile}
                if [ $? -ne 0 ]
                then
                    echo "GW-ERROR: Could not filter Clone data for ${repertoire_id} from ${data_file}"
                    continue
                fi
                # Repeat for expression data.
                echo "GW-INFO: Splitting Expression file ${expression_file} by ${SPLIT_FIELD} ${repertoire_id}"
                python3 ${SCRIPT_DIR}/${GATEWAY_UTIL_DIR}/filter-json.py $expression_file CellExpression ${SPLIT_FIELD} ${repertoire_id} > ${repository_name}/${repertoire_dirname}/${gex_datafile}
                if [ $? -ne 0 ]
                then
                    echo "GW-ERROR: Could not filter Expression data for ${repertoire_id} from ${expression_file}"
                    continue
                fi
                # Repeat for rearrangement data.
                #python3 ${SCRIPT_DIR}/${GATEWAY_UTIL_DIR}/filter.py $data_file ${SPLIT_FIELD} ${repertoire_id} ${repository_name}/${repertoire_dirname}/${repertoire_datafile}
                link_ids=( `python3 ${SCRIPT_DIR}/${GATEWAY_UTIL_DIR}/preprocess-json.py ${repository_name}/${repertoire_dirname}/${cell_datafile} Cell ${LINK_FIELD} | sort -u | awk '{printf("%s ",$0)}'` )
                if [ ${#link_ids[@]} != 1 ]
                then
                    echo "GW-ERROR: Analysis expexts a single ${LINK_FIELD} per Cell repertoire."
                    echo "GW-ERROR: Link fields = ${link_ids[@]}."
                    continue
                fi
                link_id=${link_ids[0]}
                echo "GW-INFO: Link ID = -${link_id}-"
                echo "GW-INFO: Link Field = -${LINK_FIELD}-"
                echo "GW-INFO: Input file = ${rearrangement_file}"
                echo "GW-INFO: Output file = ${rearrangement_datafile}"

                echo "GW-INFO: Splitting Rearrangement file ${rearrangement_file} by ${LINK_FIELD} ${link_id}"
                python3 ${SCRIPT_DIR}/${GATEWAY_UTIL_DIR}/filter.py $rearrangement_file ${LINK_FIELD} ${link_id} ${repository_name}/${repertoire_dirname}/${rearrangement_datafile}
                if [ $? -ne 0 ]
                then
                    echo "GW-ERROR: Could not filter Rearrangement data for ${link_id} from ${rearrangement_file}"
                    continue
                fi
                #wget https://gateway-analysis-dev.ireceptor.org/storage/test/single-cell-repo.tsv
                #mv single-cell-repo.tsv ${repository_name}/${repertoire_dirname}/${rearrangement_datafile}
        
                # Create the repertoire manifest file
                echo '{"Info":{},"DataSets":[{' > $REPERTOIRE_MANIFEST
                echo "\"cell_file\":[\"${cell_datafile}\"]," >> $REPERTOIRE_MANIFEST
                echo "\"expression_file\":[\"${gex_datafile}\"]," >> $REPERTOIRE_MANIFEST
                echo "\"rearrangement_file\":[\"${rearrangement_datafile}\"]" >> $REPERTOIRE_MANIFEST
                echo "}]}" >> $REPERTOIRE_MANIFEST

                # Call the client supplied "run_analysis" callback function. Parameters:
                #     $1 output directory
                #     $2 repository name
                #     $3 repertoire id ("NULL" if not used)
                #     $4 repertoire JSON file ["NULL" if not used, required if repertoire_id is specified]
                #     $5 manifest file
                run_cell_analysis ${repository_name}/${repertoire_dirname} ${repository_name} ${repertoire_id} ${repertoire_file} ${REPERTOIRE_MANIFEST}
            fi

            repertoire_count=$((repertoire_count+1))
        done
        count=$((count+1))
    done

    # Clean up the files we created
    # First the ZIP file
    rm -f ${ZIP_FILE}
    # Remove any data files extracted from the ZIP - they are big and can be re-generated
    for f in "${data_files[@]}"; do
        rm -f $f
    done

    # Return to the directory we started from.
    popd > /dev/null
    echo "GW-INFO: Done splitting AIRR Repertoires"

}

# Write a message saying loaded OK
echo "GW-INFO: Done loading iReceptor Gateway Utilities"

