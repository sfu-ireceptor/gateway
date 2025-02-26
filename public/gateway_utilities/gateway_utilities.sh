#!/bin/bash

echo "GW-INFO: iReceptor Gateway Utilities"

# Define the directory to use for Gateway analysis output.
export GATEWAY_ANALYSIS_DIR="gateway_analysis"
export GATEWAY_OBJECT_COUNT=0

function gateway_get_singularity() {
# Parameters
#   $1 - Name of singularity image to get from the Gateway
#   $2 - Directory in which to save the image

    # The singularity image file (sif file) to download
    local GW_SINGULARITY_FILE=$1

    # The directory to download the singularity image into
    local GW_WORKING_DIR=$2

    # Go to the destination directory
    echo "GW-INFO: Getting singularity image ${GW_SINGULARITY_FILE} from ${IR_GATEWAY_URL} started at: `date`" 
    pushd ${GW_WORKING_DIR} > /dev/null
    if [ $? -ne 0 ]
    then
        echo "GW-ERROR: Could not changed directory to ${GW_WORKING_DIR}"
        return $?
    fi

    # Get the file from the Gateway
    wget -nv ${IR_GATEWAY_URL}/storage/singularity/${GW_SINGULARITY_FILE}
    if [ $? -ne 0 ]
    then
        echo "GW-ERROR: Could not copy ${GW_SINGULARITY_FILE} from ${IR_GATEWAY_URL}"
        return
    fi

    echo "GW-INFO: Saved singularity image ${GW_SINGULARITY_FILE} in ${GW_WORKING_DIR}, completed at: `date`" 

    # Go back to where we started
    popd > /dev/null
}

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
    if [ $? -ne 0 ]
    then
        echo "GW-ERROR: Could not create ${WORKING_DIR}"
        return
    fi

    # Move the ZIP file to the working directory
    cp ${ZIP_FILE} ${WORKING_DIR}
    if [ $? -ne 0 ]
    then
        echo "GW-ERROR: Could not copy ${ZIP_FILE} to ${WORKING_DIR}"
        return
    fi

    # Move into the working directory to do work...
    pushd ${WORKING_DIR} > /dev/null

    # Uncompress zip file
    echo "GW-INFO: Extracting files into ${WORKING_DIR} started at: `date`" 
    unzip -o "$ZIP_FILE" 
    if [ $? -ne 0 ]
    then
        echo "GW-ERROR: Could not unzip ${ZIP_FILE} to ${WORKING_DIR}"
        exit 1
    fi

    echo "GW-INFO: Extracting files finished at: `date`" 

    # Remove the copied ZIP file.
    rm -f ${ZIP_FILE} 

    # Go back to where we started
    popd > /dev/null
}

function gateway_cleanup(){
# Parameters:
#     $1 - ZIP File
#     $2 - AIRR Manifest file
#     $3 - Working directory
#     $4 - Analysis type (optional - default = "rearrangement_file"

    echo "GW-INFO: ========================================"
    echo -n "GW-INFO: Stating to clean up files at "
    date
    # The Gateway provides information about the download in the file info.txt and
    # an AIRR Manifest JSON file.
    local ZIP_FILE=$1
    local MANIFEST_FILE=$2
    # We want a working directory for the processing
    local WORKING_DIR=$3
    # The type of analysis to do
    local ANALYSIS_TYPE=""
    if [ -z "$4" ]; then
        ANALYSIS_TYPE="rearrangement_file"
    else
        ANALYSIS_TYPE=$4
    fi

    # Move into the working directory to do work...
    pushd ${WORKING_DIR} > /dev/null

    # Determine the files that were extracted for the computation
    if [ ${ANALYSIS_TYPE} = "rearrangement_file" ]
    then
        data_files=( `python3 ${IR_GATEWAY_UTIL_DIR}/manifest_summary.py ${MANIFEST_FILE} ${ANALYSIS_TYPE}` )
    elif [ ${ANALYSIS_TYPE} = "clone_file" ]
    then
        data_files=( `python3 ${IR_GATEWAY_UTIL_DIR}/manifest_summary.py ${MANIFEST_FILE} ${ANALYSIS_TYPE}` )
    elif [ ${ANALYSIS_TYPE} = "cell_file" ]
    then
        # Cell analyses have three different types of files to process.
        data_files=( `python3 ${IR_GATEWAY_UTIL_DIR}/manifest_summary.py ${MANIFEST_FILE} ${ANALYSIS_TYPE}` )
        expression_files=( `python3 ${IR_GATEWAY_UTIL_DIR}/manifest_summary.py ${MANIFEST_FILE} "expression_file"` )
        rearrangement_files=( `python3 ${IR_GATEWAY_UTIL_DIR}/manifest_summary.py ${MANIFEST_FILE} "rearrangement_file"` )
    fi

    # Clean up the files we created. First the ZIP file
    echo "GW-INFO: Removing ${ZIP_FILE}"
    rm -f ${ZIP_FILE}

    # Remove any data files extracted from the ZIP - they are big and can be re-generated
    for f in "${data_files[@]}"; do
        echo "GW-INFO: Removing ${f}"
        rm -f $f
    done
    for f in "${expression_files[@]}"; do
        echo "GW-INFO: Removing ${f}"
        rm -f $f
    done
    for f in "${rearrangement_files[@]}"; do
        echo "GW-INFO: Removing ${f}"
        rm -f $f
    done
    echo -n "GW-INFO: Done cleaning up files at "
    date
    echo "GW-INFO: ========================================"

    popd > /dev/null
}

function gateway_run_analysis(){
# Parameters:
#     $1 - iReceptor info.txt file
#     $2 - AIRR Manifest file
#     $3 - Working directory
#     $4 - Analysis type (optional - default = "rearrangement_file"

    echo "GW-INFO: ========================================"
    echo -n "GW-INFO: Running Analyses at "
    date
    # Keep track of timing and count of objects processed
    local start_seconds=$SECONDS
    local num_objects=0

    # The Gateway provides information about the download in the file info.txt and
    # an AIRR Manifest JSON file.
    local INFO_FILE=$1
    local MANIFEST_FILE=$2
    # We want a working directory for the processing
    local WORKING_DIR=$3
    # The type of analysis to do
    local ANALYSIS_TYPE=""
    if [ -z "$4" ]; then
        ANALYSIS_TYPE="rearrangement_file"
    else
        ANALYSIS_TYPE=$4
    fi
    echo "GW-INFO: Analysis type = ${ANALYSIS_TYPE}"
    
    # We need a field on which to split the data.
    SPLIT_FIELD="repertoire_id"
    LINK_FIELD="data_processing_id"

    # Move into the working directory to do work...
    pushd ${WORKING_DIR} > /dev/null

    # Get the repository from the manifest file.
    repository_urls=( `python3 ${IR_GATEWAY_UTIL_DIR}/manifest_summary.py ${MANIFEST_FILE} "repository_url"` )
    echo "GW-INFO: Repository URLs = ${repository_urls[@]}"

    # Get the Reperotire files from the manifest file.
    repertoire_files=( `python3 ${IR_GATEWAY_UTIL_DIR}/manifest_summary.py ${MANIFEST_FILE} "repertoire_file"` )
    echo "GW-INFO: Repertoire files = ${repertoire_files[@]}"

    # Determine the files to process. We extract the data files from the AIRR-manifest.json
    # and store them in an array. The type is one of rearrangement_file, cell_file, clone_file
    if [ ${ANALYSIS_TYPE} = "rearrangement_file" ]
    then
        data_files=( `python3 ${IR_GATEWAY_UTIL_DIR}/manifest_summary.py ${MANIFEST_FILE} ${ANALYSIS_TYPE}` )
    elif [ ${ANALYSIS_TYPE} = "clone_file" ]
    then
        data_files=( `python3 ${IR_GATEWAY_UTIL_DIR}/manifest_summary.py ${MANIFEST_FILE} ${ANALYSIS_TYPE}` )
    elif [ ${ANALYSIS_TYPE} = "cell_file" ]
    then
        # Cell analyses have three different types of files to process.
        data_files=( `python3 ${IR_GATEWAY_UTIL_DIR}/manifest_summary.py ${MANIFEST_FILE} ${ANALYSIS_TYPE}` )
        expression_files=( `python3 ${IR_GATEWAY_UTIL_DIR}/manifest_summary.py ${MANIFEST_FILE} "expression_file"` )
        rearrangement_files=( `python3 ${IR_GATEWAY_UTIL_DIR}/manifest_summary.py ${MANIFEST_FILE} "rearrangement_file"` )
    fi

    # Check to make sure we have some data files to process in the manifest file.
    echo "GW-INFO: Data files = ${data_files[@]}"
    if [ ${#data_files[@]} -eq 0 ]; then
        echo "GW-ERROR: Could not find any ${ANALYSIS_TYPE} in ${MANIFEST_FILE}"
        exit $?
    fi
    
    # For each repository, process the data from it.
    count=0
    repertoire_total=0
    for repository_url in "${repository_urls[@]}"; do
        repertoire_count=0

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

        if [ ! -f ${data_file} ]; then
            echo "GW-ERROR: Could not find data file ${data_file}"
            continue
        fi
                # Get an array of unique repertoire_ids from the data file
        echo "GW-INFO:     Processing ${SPLIT_FIELD} from $data_file"
        if [ ${ANALYSIS_TYPE} = "rearrangement_file" ]
        then
            # preprocess.py dumps a field of interest from a TSV data file. We want
            # all of the reperotire_ids - we sort them to get unique ids and then
            # use awk to print them all on the same line to create an array
            # of repertoire_ids
            repertoire_ids=( `python3 ${IR_GATEWAY_UTIL_DIR}/preprocess.py $data_file $SPLIT_FIELD | sort -u | awk '{printf("%s ",$0)}'` )
        elif [ ${ANALYSIS_TYPE} = "cell_file" ]
        then
            # preprocess-json.py dumps a field of interest from a JSON data file. We want
            # all of the reperotire_ids - we sort them to get unique ids and then
            # use awk to print them all on the same line to create an array
            # of repertoire_ids
            repertoire_ids=( `python3 ${IR_GATEWAY_UTIL_DIR}/preprocess-json.py $data_file Cell $SPLIT_FIELD | sort -u | awk '{printf("%s ",$0)}'` )
        elif [ ${ANALYSIS_TYPE} = "clone_file" ]
        then
            # preprocess-json.py dumps a field of interest from a JSON data file. We want
            # all of the reperotire_ids - we sort them to get unique ids and then
            # use awk to print them all on the same line to create an array
            # of repertoire_ids
            repertoire_ids=( `python3 ${IR_GATEWAY_UTIL_DIR}/preprocess-json.py $data_file Clone $SPLIT_FIELD | sort -u | awk '{printf("%s ",$0)}'` )
        else
            echo "GW-ERROR: Do not know how to split repertoires for ${ANALYSIS_TYPE}"
            exit 1
        fi

        # For each repertoire_id, extract the data in a directory for that repertoire
        for repertoire_id in "${repertoire_ids[@]}"; do
            # Get a directory name and make the directory
            echo "GW-INFO: File $data_file has repertoire_id = ${repertoire_id}"
            repertoire_dirname=${repertoire_id}
            # Generate the manifest file name for this analysis unit
            REPERTOIRE_MANIFEST=${repository_name}/${repertoire_dirname}/manifest.json
            echo "GW-INFO: Manifest file = ${REPERTOIRE_MANIFEST}"
            echo "GW-INFO: Analyis type = ${ANALYSIS_TYPE}"

            if [ ${ANALYSIS_TYPE} = "rearrangement_file" ]
            then
                # Call the client supplied "run_analysis" callback function. Parameters:
                #     $1 output directory
                #     $2 repository name
                #     $3 repertoire id ("NULL" if not used)
                #     $4 repertoire JSON file ["NULL" if not used, required if repertoire_id is specified]
                #     $5 manifest file
                echo "GW-INFO: Inputs"
                echo "GW-INFO: ${repository_name}/${repertoire_dirname}"
                echo "GW-INFO: ${repository_name}"
                echo "GW-INFO: ${repertoire_id}"
                echo "GW-INFO: ${repertoire_file}"
                echo "GW-INFO: ${REPERTOIRE_MANIFEST}"
                run_analysis ${repository_name}/${repertoire_dirname} ${repository_name} ${repertoire_id} ${repertoire_file} ${REPERTOIRE_MANIFEST} ${ANALYSIS_TYPE}

            elif [ ${ANALYSIS_TYPE} = "clone_file" ]
            then
                # Call the client supplied "run_analysis" callback function. Parameters:
                #     $1 output directory
                #     $2 repository name
                #     $3 repertoire id ("NULL" if not used)
                #     $4 repertoire JSON file ["NULL" if not used, required if repertoire_id is specified]
                #     $5 manifest file
                run_analysis ${repository_name}/${repertoire_dirname} ${repository_name} ${repertoire_id} ${repertoire_file} ${REPERTOIRE_MANIFEST} ${ANALYSIS_TYPE}
            elif [ ${ANALYSIS_TYPE} = "cell_file" ]
            then
                # Call the client supplied "run_analysis" callback function. Parameters:
                #     $1 output directory
                #     $2 repository name
                #     $3 repertoire id ("NULL" if not used)
                #     $4 repertoire JSON file ["NULL" if not used, required if repertoire_id is specified]
                #     $5 manifest file
                echo -n "GW-INFO: Running Cell analysis ${repository_name}/${repertoire_dirname} - "
                date
                run_analysis ${repository_name}/${repertoire_dirname} ${repository_name} ${repertoire_id} ${repertoire_file} ${REPERTOIRE_MANIFEST} ${ANALYSIS_TYPE}
                echo -n "GW-INFO: Done running Cell analysis ${repository_name}/${repertoire_dirname} - "
                date
            fi

            repertoire_count=$((repertoire_count+1))
        done
        echo "GW-INFO:" 
        echo "GW-INFO:" 
        repertoire_total=$((repertoire_total+repertoire_count))
        count=$((count+1))
    done
    popd > /dev/null
    echo -n "GW-INFO: Done running analyses at " 
    date
    end_seconds=$SECONDS
    total_seconds=$((end_seconds-start_seconds))
    object_per_second=$((GATEWAY_OBJECT_COUNT/total_seconds))
    echo "GW-INFO: Total analysis time (s) = $total_seconds" 
    echo "GW-INFO: Total repertoires =  $repertoire_total" 
    echo "GW-INFO: Total objects processed = $GATEWAY_OBJECT_COUNT"
    echo "GW-INFO: Total objects/second = $object_per_second"
    echo "GW-INFO: ========================================"
}

function gateway_split_repertoire(){
# Parameters:
#     $1 - iReceptor info.txt file
#     $2 - AIRR Manifest file
#     $3 - iReceptor ZIP file
#     $4 - Working directory
#     $5 - Type of analysis from the manifest (rearrangement_file, clone_file, cell_file)

    echo "GW-INFO: ========================================"
    echo -n "GW-INFO: Splitting AIRR Repertoires at "
    date
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
        data_files=( `python3 ${IR_GATEWAY_UTIL_DIR}/manifest_summary.py ${MANIFEST_FILE} ${ANALYSIS_TYPE}` )
    elif [ ${ANALYSIS_TYPE} = "clone_file" ]
    then
        data_files=( `python3 ${IR_GATEWAY_UTIL_DIR}/manifest_summary.py ${MANIFEST_FILE} ${ANALYSIS_TYPE}` )
    elif [ ${ANALYSIS_TYPE} = "cell_file" ]
    then
        # Cell analyses have three different types of files to process.
        data_files=( `python3 ${IR_GATEWAY_UTIL_DIR}/manifest_summary.py ${MANIFEST_FILE} ${ANALYSIS_TYPE}` )
        expression_files=( `python3 ${IR_GATEWAY_UTIL_DIR}/manifest_summary.py ${MANIFEST_FILE} "expression_file"` )
        rearrangement_files=( `python3 ${IR_GATEWAY_UTIL_DIR}/manifest_summary.py ${MANIFEST_FILE} "rearrangement_file"` )
    fi

    # Check to make sure we have some data files to process in the manifest file.
    echo "GW-INFO: Data files = ${data_files[@]}"
    if [ ${#data_files[@]} -eq 0 ]; then
        echo "GW-ERROR: Could not find any ${ANALYSIS_TYPE} in ${MANIFEST_FILE}"
        exit 1
    fi

    # Get the repository from the manifest file.
    repository_urls=( `python3 ${IR_GATEWAY_UTIL_DIR}/manifest_summary.py ${MANIFEST_FILE} "repository_url"` )
    echo "GW-INFO: Repository URLs = ${repository_urls[@]}"

    # Get the Reperotire files from the manifest file.
    repertoire_files=( `python3 ${IR_GATEWAY_UTIL_DIR}/manifest_summary.py ${MANIFEST_FILE} "repertoire_file"` )
    echo "GW-INFO: Repertoire files = ${repertoire_files[@]}"

    # For each repository, process the data from it.
    count=0
    repertoire_total=0
    for repository_url in "${repository_urls[@]}"; do
        # Get the files to process for each repository. This assumes that there is
        # one data file and repertoire file  per repository
        data_file=${data_files[$count]}
        repertoire_file=${repertoire_files[$count]}
        # Get the repository name (FQDN) of the repository
        repository_name=`echo "$repository_url" | awk -F/ '{print $3}'`
        echo "GW-INFO:"
        echo -n "GW-INFO: Processing data from repository ${repository_name} at "
        date
        echo "GW-INFO: Repertoire file = ${repertoire_file}"
        echo "GW-INFO: Data file = ${data_file}"

        # Get an array of unique repertoire_ids from the data file
        echo -n "GW-INFO:     Extracting ${SPLIT_FIELD} from $data_file at "
        date
        if [ ${ANALYSIS_TYPE} = "rearrangement_file" ]
        then
            # preprocess.py dumps a field of interest from a TSV data file. We want
            # all of the reperotire_ids - we sort them to get unique ids and then
            # use awk to print them all on the same line to create an array
            # of repertoire_ids
            repertoire_ids=( `python3 ${IR_GATEWAY_UTIL_DIR}/preprocess.py $data_file $SPLIT_FIELD | sort -u | awk '{printf("%s ",$0)}'` )
        elif [ ${ANALYSIS_TYPE} = "cell_file" ]
        then
            # preprocess-json.py dumps a field of interest from a JSON data file. We want
            # all of the reperotire_ids - we sort them to get unique ids and then
            # use awk to print them all on the same line to create an array
            # of repertoire_ids
            repertoire_ids=( `python3 ${IR_GATEWAY_UTIL_DIR}/preprocess-json.py $data_file Cell $SPLIT_FIELD | sort -u | awk '{printf("%s ",$0)}'` )
        elif [ ${ANALYSIS_TYPE} = "clone_file" ]
        then
            # preprocess-json.py dumps a field of interest from a JSON data file. We want
            # all of the reperotire_ids - we sort them to get unique ids and then
            # use awk to print them all on the same line to create an array
            # of repertoire_ids
            repertoire_ids=( `python3 ${IR_GATEWAY_UTIL_DIR}/preprocess-json.py $data_file Clone $SPLIT_FIELD | sort -u | awk '{printf("%s ",$0)}'` )
        else
            echo "GW-ERROR: Do not know how to split repertoires for ${ANALYSIS_TYPE}"
            exit 1
        fi

        # Create a directory for each repository (mkdir if it isn't already with -p)
        mkdir -p ${repository_name}
        
        #
        # Perform any repertoire by repertoire data transformation. Some data is split
        # a repertoire at a time, some data is split into repertoires as one operation
        # on a single file (see below).
        #
        repertoire_count=0
        # For each repertoire_id, extract the data in a directory for that repertoire
        for repertoire_id in "${repertoire_ids[@]}"; do
            echo -n "GW-INFO: Starting processing ${repertoire_id} from file ${data_file} at "
            date

            # Get a directory name and make the directory
            repertoire_dirname=${repertoire_id}
            mkdir -p ${repository_name}/${repertoire_dirname}
            if [ $? -ne 0 ]
            then
                echo "GW-ERROR: Could not repertoire directory ${repository_name}/${repertoire_dirname}"
                continue
            fi

            # Copy the HTML resources for the Apps
            echo "GW-INFO: Copying HTML assets"
            mkdir -p ${repository_name}/${repertoire_dirname}/assets
            cp -r ${IR_GATEWAY_UTIL_DIR}/assets/* ${repository_name}/${repertoire_dirname}/assets
            if [ $? -ne 0 ]
            then
                echo "GW-ERROR: Could not create HTML asset directory"
            fi
             
            # Generate the manifest file name for this analysis unit
            REPERTOIRE_MANIFEST=${repository_name}/${repertoire_dirname}/manifest.json
            echo "GW-INFO: Manifest file = ${REPERTOIRE_MANIFEST}"
            echo "GW-INFO: Analyis type = ${ANALYSIS_TYPE}"

            # Based on the type of analysis, split the data out for this reperotire_id
            if [ ${ANALYSIS_TYPE} = "rearrangement_file" ]
            then
                # Generate a file name for the TSV data for the repertoire.
                repertoire_datafile=${repertoire_dirname}".tsv"
    
                # For rearrangements, we split data by repertoire in one pass, this is done
                # below. It is very inefficient to traverse very large rearrangement files
                # once for each repertoire. Here we just build the manifest file.
        
                # Create the repertoire manifest file
                echo '{"Info":{},"DataSets":[' > $REPERTOIRE_MANIFEST
                echo "{\"rearrangement_file\":[\"${repertoire_datafile}\"]}" >> $REPERTOIRE_MANIFEST
                echo "]}" >> $REPERTOIRE_MANIFEST

            elif [ ${ANALYSIS_TYPE} = "clone_file" ]
            then
                # Generate a file name for the TSV data for the repertoire.
                repertoire_datafile=${repertoire_dirname}".json"
    
                # Filter the input file $data_file and extract all records that have the given
                # repertoire_id in the SPLIT_FIELD.
                # Command line parameters: inputfile, field_name, field_value, outfile
                python3 ${IR_GATEWAY_UTIL_DIR}/filter-json.py $data_file Clone ${SPLIT_FIELD} ${repertoire_id} ${repository_name}/${repertoire_dirname}/${repertoire_datafile}
                if [ $? -ne 0 ]
                then
                    echo "GW-ERROR: Could not filter Clone data for ${repertoire_id} from ${data_file}"
                    continue
                fi
                echo -n "GW-INFO: Clone file contains: "
                wc -l ${repository_name}/${repertoire_dirname}/${repertoire_datafile}
        
                # Create the repertoire manifest file
                echo '{"Info":{},"DataSets":[' > $REPERTOIRE_MANIFEST
                echo "{\"clone_file\":[\"${repertoire_datafile}\"]}" >> $REPERTOIRE_MANIFEST
                echo "]}" >> $REPERTOIRE_MANIFEST

            elif [ ${ANALYSIS_TYPE} = "cell_file" ]
            then
                # Get the expression and rearrangement files that accompany this analysis unit.
                echo "GW-INFO: Expression files = ${expression_files[@]}"
                echo "GW-INFO: Rearrangement files = ${rearrangement_files[@]}"
                expression_file=${expression_files[$count]}
                rearrangement_file=${rearrangement_files[$count]}
                # Generate file names for the data for the repertoire.
                cell_datafile=${repertoire_dirname}"-cell.json"
                gex_datafile=${repertoire_dirname}"-gex.h5ad"
                rearrangement_datafile=${repertoire_dirname}"-rearrangement.tsv"
    
                # Filter the input file extract all records that have the given
                # repertoire_id in the SPLIT_FIELD.
                # Command line parameters: inputfile, field_name, field_value, outfile
                echo "GW-INFO: Splitting Cell file ${data_file} by ${SPLIT_FIELD} ${repertoire_id}"
                python3 ${IR_GATEWAY_UTIL_DIR}/filter-json.py $data_file Cell ${SPLIT_FIELD} ${repertoire_id} ${repository_name}/${repertoire_dirname}/${cell_datafile}
                if [ $? -ne 0 ]
                then
                    echo "GW-ERROR: Could not filter Cell data for ${repertoire_id} from ${data_file}"
                    continue
                fi
                echo -n "GW-INFO: Done splitting Cell file - "
                date

                # NOTE: Expression data is not split repertoire by repertoire. It is
                # split into many repertoire files at one time (see below).

                # Handle the rearrangement files. 
                # First we get a set of unique linking field IDs from the Cell file, all on one line, space separated. 
                # We expect only one of these per repertoire.
                #link_ids=( `python3 ${IR_GATEWAY_UTIL_DIR}/preprocess-json.py ${repository_name}/${repertoire_dirname}/${cell_datafile} Cell ${LINK_FIELD} | sort -u | awk '{printf("%s ",$0)}'` )
                cell_id_tmp=$(mktemp)
                python3 ${IR_GATEWAY_UTIL_DIR}/preprocess-json.py ${repository_name}/${repertoire_dirname}/${cell_datafile} Cell cell_id | sort -u > $cell_id_tmp
                grep -f $cell_id_tmp $rearrangement_file > ${repository_name}/${repertoire_dirname}/${rearrangement_datafile}

                #if [ ${#link_ids[@]} != 1 ]
                #then
                    #echo "GW-ERROR: Analysis expects a single ${LINK_FIELD} per Cell repertoire."
                    #echo "GW-ERROR: Link fields = ${link_ids[@]}."
                    #continue
                #fi

                # Filter the rearrangement file based on the Link field and the link ID we got above.
                #link_id=${link_ids[0]}
                #echo "GW-INFO: Link ID = -${link_id}-"
                #echo "GW-INFO: Link Field = -${LINK_FIELD}-"
                echo "GW-INFO: Input file = ${rearrangement_file}"
                echo "GW-INFO: Output file = ${rearrangement_datafile}"
                #echo "GW-INFO: Splitting Rearrangement file ${rearrangement_file} by ${LINK_FIELD} ${link_id}"
                #python3 ${IR_GATEWAY_UTIL_DIR}/filter.py $rearrangement_file ${LINK_FIELD} ${link_id} ${repository_name}/${repertoire_dirname}/${rearrangement_datafile}
                #if [ $? -ne 0 ]
                #then
                    #echo "GW-ERROR: Could not filter Rearrangement data for ${link_id} from ${rearrangement_file}"
                    #continue
                #fi
        
                # Create the repertoire manifest file. NOTE: We don't create the GEX file here, it
                # is created at the same time as all of the GEX data for all of the repertoires, but
                # we do add it to the manifest file here.
                echo '{"Info":{},"DataSets":[{' > $REPERTOIRE_MANIFEST
                echo "\"cell_file\":[\"${cell_datafile}\"]," >> $REPERTOIRE_MANIFEST
                echo "\"expression_file\":[\"${gex_datafile}\"]," >> $REPERTOIRE_MANIFEST
                echo "\"rearrangement_file\":[\"${rearrangement_datafile}\"]" >> $REPERTOIRE_MANIFEST
                echo "}]}" >> $REPERTOIRE_MANIFEST
            fi

            # Increase our count so we loop through the repertoires correctly.
            repertoire_count=$((repertoire_count+1))

            # Print out a done message
            echo -n "GW-INFO: Done processing ${repertoire_id} from file ${data_file} at "
            date
        done

        #
        # Perform any bulk data transformation (data is split into repertoires in one step). Most
        # data is currently processed a repertoire at a time. Doing it all at once is more
        # efficient. 
        #
        # Currently we do this for cell GEX files only.
        if [ ${ANALYSIS_TYPE} = "cell_file" ]
        then
            echo "GW-INFO: Splitting Expression file ${expression_file} by ${SPLIT_FIELD}"

            # Split the GEX input file into N files one per repertoire, converting the
            # data from JSON to h5ad for downstream processing. Output goes in the
            # current directory. Output files are named $repertoire_id.h5ad. 
            #
            python3 ${IR_GATEWAY_UTIL_DIR}/gateway-airr-to-h5ad.py -v \
                ${expression_file} . 'CellExpression' ${SPLIT_FIELD}
            if [ $? -ne 0 ]
            then
                echo "GW-ERROR: Cell split failed on file ${expression_file}"
                return
            fi

            repertoire_count=0
            # For each repertoire, move h5ad file into the correct directory for that
            # repertoire.
            for repertoire_id in "${repertoire_ids[@]}"; do
                # Get the directory based on the recipe from above. A directory
                # per repertoire.
                repertoire_dirname=${repertoire_id}
                echo "GW-INFO: Moving ${repertoire_id}.h5ad to analysis file ${repository_name}/${repertoire_dirname}/${repertoire_id}-gex.h5ad"
                # Move the file into the directory with the appropriate name, matching the file
                # naming convention as per the above repertoire split methodology.
                mv ${repertoire_id}.h5ad ${repository_name}/${repertoire_dirname}/${repertoire_id}-gex.h5ad
                if [ $? -ne 0 ]
                then
                    echo "GW-ERROR: Could not move ${repertoire_id}.h5ad to analysis file ${repository_name}/${repertoire_dirname}/${repertoire_id}-gex.h5ad"
                    continue
                fi
            done
            echo -n "GW-INFO: Done splitting Expression file - "
            date

        fi

        # Split Rearrangement files. Both Cell and Rearrangement analyses have rearrangement files to split.
        # if [ ${ANALYSIS_TYPE} = "rearrangement_file" ] || [ ${ANALYSIS_TYPE} = "cell_file" ]
        # For now we split cell rearrangements a repertoire at a time above - these files are small
        # and require more work to split in one pass as we do here. This is an optimization that we
        # should do.
        if [ ${ANALYSIS_TYPE} = "rearrangement_file" ] 
        then
            echo -n "GW-INFO: Splitting Rearrangement file ${data_file} by ${SPLIT_FIELD} - "
            date
            # Create a temporary directory to work in
            #TMP_DIR=${IR_JOB_DIR}/${WORKING_DIR}/tmp
            TMP_DIR=${PWD}/tmp
            mkdir ${TMP_DIR}

            # Create a header line for each repertoire based rearrangement file.
            for repertoire_id in "${repertoire_ids[@]}"; do
                echo "GW-INFO: Creating ${TMP_DIR}/${repertoire_id}.tsv"
                head -n 1 ${data_file} > ${TMP_DIR}/${repertoire_id}.tsv
            done

            # Get the column for the SPLIT_FIELD
            repertoire_id_column=`head -n 1 ${data_file} | awk -F"\t" -v label=${SPLIT_FIELD} '{for(i=1;i<=NF;i++){if ($i == label){print i}}}'`
            if [ $? -ne 0 ]
            then
                echo "GW-ERROR: Could not find ${SPLIT_FIELD} column in ${data_file}"
                continue
            fi
	        echo "GW-INFO: Using column ${repertoire_id_column} for ${SPLIT_FIELD}"

            # Split the file into N files based on SPLIT_FIELD.
            # AWK is pretty efficient at this
            awk -F '\t' -v tmpdir=${TMP_DIR} -v column=${repertoire_id_column} '{if (NR>1) {print $0 >> tmpdir"/"$column".tsv"}}' ${data_file}
            if [ $? -ne 0 ]
            then
                echo "GW-ERROR: Could not split ${data_file} on field ${SPLIT_FIELD}"
                continue
            fi
            echo "GW-INFO: Files generated by split:"
            for split_file in ${TMP_DIR}/*; do
                line_count=`wc -l $split_file | cut -d ' ' -f 1`
                echo "GW-INFO:     $line_count $split_file"
                GATEWAY_OBJECT_COUNT=$((GATEWAY_OBJECT_COUNT+line_count))
            done
            echo "GW-INFO:     Total = $GATEWAY_OBJECT_COUNT"

            # Move the file from its temp location to its final location.
            for repertoire_id in "${repertoire_ids[@]}"; do
                echo "GW-INFO: Moving ${TMP_DIR}/${repertoire_id}.tsv to ${repository_name}/${repertoire_id}/"
                #mv ${TMP_DIR}/${repertoire_id}.tsv ${repository_name}/${repertoire_id}/
                cp ${TMP_DIR}/${repertoire_id}.tsv ${repository_name}/${repertoire_id}/
            done

            # Remove the temporary files/directories that remain.
            rm -rf ${TMP_DIR}
            echo -n "GW-INFO: Done splitting Rearrangement file ${data_file} - "
            date
        fi
        echo "GW-INFO:" 
        echo "GW-INFO:" 
        count=$((count+1))
    done

    # Return to the directory we started from.
    popd > /dev/null
    echo -n "GW-INFO: Done splitting AIRR Repertoires at "
    date
    echo "GW-INFO: ========================================"

}

# Write a message saying loaded OK
echo "GW-INFO: Done loading iReceptor Gateway Utilities"

