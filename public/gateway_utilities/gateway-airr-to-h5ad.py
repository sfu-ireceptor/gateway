import string
import sys
import argparse
import json
import time
import pandas
import numpy
import scanpy
import anndata
from scipy.sparse import csr_matrix
from scipy import sparse
from pandas.api.types import CategoricalDtype

def createAnnData(cell_dict_array, field, value):
    # cell_dict_array: An array of JSON expression objects for a single repertoire
    # field: The field (repertoire_id) we are using for processing 
    # value: The value of field for the repertoire we are processing.
    
    # Get a data frame from the array of cells dictionary
    #print('IR-INFO: CreateAnnData - creating pandas data frame %d'%(time.perf_counter()), flush=True)
    df = pandas.DataFrame.from_records(cell_dict_array)

    # Get the unique cells
    cells = df["cell_id"].unique()
    #print('IR-INFO: number of cells = %d'%(len(cells)), flush=True)
    #print('IR-INFO: field = %s, value = %s'%(field, value), flush=True)
    #print('IR-INFO: cells = %s'%(str(cells)), flush=True)
    
    # Get the unique property labels and IDs. 
    property_dict_array = df["property"]
    label_series = pandas.Series([d['label'] for d in property_dict_array])
    id_series = pandas.Series([d['id'] for d in property_dict_array])
    property_labels = label_series.unique()
    property_ids = id_series.unique()

    # Get the shape of our object (cells x properties)
    shape = (len(cells), len(property_labels))
    
    # Create categorical cells and properties
    cell_cat = CategoricalDtype(cells)
    property_cat = CategoricalDtype(property_labels)

    # Create our indexes 
    cell_index = df["cell_id"].astype(cell_cat).cat.codes
    property_index = label_series.astype(property_cat).cat.codes

    # Create the sparse matrix
    #print('IR-INFO: Creating coo matrix %d'%(time.perf_counter()), flush=True)
    coo = sparse.coo_matrix((df["value"], (cell_index, property_index)), shape=shape)
    #print('IR-INFO: Creating csr matrix %d'%(time.perf_counter()), flush=True)
    csr = coo.tocsr()

    # Create the AnnData object from the sparse matrix
    #print('IR-INFO: Creating adata %d'%(time.perf_counter()), flush=True)
    adata = anndata.AnnData(csr, dtype=numpy.float64)

    # Assign some useful observation attributes (cell_id, partition field, partition value)
    #print('IR-INFO: Assigning attributes %d'%(time.perf_counter()), flush=True)
    adata.obs['cell_id'] = cells
    adata.obs['field'] = field
    adata.obs['value'] = value

    # Assign some useful variable attributes (property.id and property.label)
    # If the number of IDs is different than the number of labels, then we have
    # a ontology ID/label conflict, so don't assign the labels and print a warning.
    adata.var['property.label'] = property_labels
    if len(property_labels) == len(property_ids):
        adata.var['property.id'] = property_ids
    else:
        print('IR-INFO: property labels and ids do not match, not storing IDs')

    # Assign the observation and variable names (cell_id and property.id)
    adata.obs_names = cells
    adata.var_names = property_labels

    # Make the var names unique
    #adata.var_names_make_unique()

    # Return the data structure.
    #print('IR-INFO: Finished createAnnData %d'%(time.perf_counter()), flush=True)
    return adata

def skipWhite(str_buffer, str_loc):
    # Skip white space starting at str_loc in str_buffer, return the loc of
    # the first non-white space character.
    while str_buffer[str_loc] in string.whitespace:
        str_loc = str_loc + 1
    return str_loc

def generateH5AD(gex_filename, block, field, buffer_size):
    print('IR-INFO: Splitting %s block from %s on field %s'%(block, gex_filename, field),flush=True)
    # Keep track of the number of properties
    #count = 0
    # An array to hold the anndata objects in
    #adata_array = []
    # Dictionaries, keyed on repertoire_id, to hold info for each repertoire.

    # We have arrays of adata objects per repertoire. These are concatenated 
    # at the end to create a single adata object per repertoire
    repertoire_cell_adata_array = dict()
    # We have JSON object arrays per repertoire. These are transient as we
    # process JSON data, and get transformed into adata objects after a
    # fixed number of objects are processed.
    repertoire_cell_json_array = dict()
    # We keep track of the cell IDs for each repertoire.
    repertoire_cell_id_array = dict()
    # We also track the number of properties processed per repertoire.
    repertoire_property_count = dict()
    # Open the JSON file.
    try:
        with open(gex_filename, 'r') as f:

            # Read in our first buffer_size characters.
            t_start = time.perf_counter()
            json_buffer = f.read(buffer_size)
            json_loc = 0

            # Skip over any whitespace.
            json_loc = skipWhite(json_buffer, json_loc)

            # AIRR JSON files are JSON objects so we expect an opening {
            if json_buffer[json_loc] != '{':
                print('IR_ERROR: JSON file %s has no opening {'%(gex_filename))
                sys.exit(1)

            # Look at the next part of the string, excluding white space.
            json_loc = skipWhite(json_buffer, json_loc+1)

            # We look for the block key in the string (enclosed in quotes of course)
            # If we can't find it in the first buffer_size characters we assume it isn't there.
            # We are cheating here - as we assume everything between the opening { for
            # the object and the block key we are interested in is correct JSON.
            json_loc = json_buffer.find('"%s"'%(block), json_loc)
            if json_loc == -1:
                print('IR_ERROR: Could not find "%s" in first %d characters of JSON file %s'%
                      (block, buffer_size, gex_filename))
                sys.exit(1)

            # Strip off the block key and any new white space
            json_loc = skipWhite(json_buffer, json_loc+len(block)+2)

            # The key should be followed by a separator :
            if json_buffer[json_loc] != ':':
                print('IR_ERROR: expected JSON : separator')
                sys.exit(1)
                
            # Strip off the array character and any white space.
            json_loc = skipWhite(json_buffer, json_loc+1)

            # The block should be an array, so we expect the array character.
            if json_buffer[json_loc] != '[':
                print('IR_ERROR: JSON object %s is not an array'%(block))
                sys.exit(1)
                
            # Strip off the array character and any white space.
            json_loc = skipWhite(json_buffer, json_loc+1)

            # Initalize our loop variables. We track whether we are still processing objects,
            # the maximum size of the objects in the array we are processing, the threshold that
            # determines how many objects we hold in the buffer before reading more data,
            # a flag to note whether the file is done processing, and a count of how many
            # objects we have written.
            processing_object = True
            object_str_size = 0
            object_threshold = 4
            file_empty = False
            while processing_object:
                # Check to see if the buffer is almost consumed. If we have room
                # for less than N objects, then read in more data. Don't read any more
                # data if the file is empty.
                buffer_remaining = buffer_size - json_loc
                if buffer_remaining < object_threshold*object_str_size and not file_empty:
                    # Loop over the data we have gathered in json array for each repertoire_id
                    for my_key in repertoire_cell_json_array:
                        # If we don't have an adata array for this key, create an empty array
                        if not my_key in repertoire_cell_adata_array:
                            repertoire_cell_adata_array[my_key] = []
                        # If we have some data in the JSON array for this key, create the Anndata
                        # object from that JSON data and add it to the adata array.
                        if len(repertoire_cell_json_array[my_key]) > 0:
                            # Create an AnnData stucture from the array of cells and append that
                            # to the array of AnnData objects. We concatenate all the partial files
                            # at the end.
                            repertoire_cell_adata_array[my_key].append(createAnnData(repertoire_cell_json_array[my_key], field, my_key))
                            print('IR-INFO: reperotire_id = %s, new = %d, total = %d, adata length = %d'% (
                                  my_key,
                                  len(repertoire_cell_json_array[my_key]),
                                  repertoire_property_count[my_key], 
                                  len(repertoire_cell_adata_array[my_key])),
                                  flush=True)
                        # We want to reset the JSON array, since all the records have been added above.
                        repertoire_cell_json_array[my_key] = []

                    t_end = time.perf_counter()
                    print('IR-INFO: time = %d s'% (t_end-t_start), flush=True)

                    # Read in the new data
                    t_start = time.perf_counter()
                    new_buffer = f.read(buffer_size)
                    # If we are at file end, note it, and continue processing
                    # If we added new data, create a new buffer with the rest of the
                    # old data combined with the new data, and reset the location of
                    # our pointer and the size of the buffer.
                    if len(new_buffer) == 0:
                        file_empty = True
                    else:
                        old_buffer = json_buffer[json_loc:]
                        json_buffer = old_buffer + new_buffer
                        json_loc = 0
                        buffer_size = len(json_buffer)

                # Check to make sure that the first charater is an object delimeter. We have run
                # strip to get rid of any whitespace so anything else is an error.
                if json_buffer[json_loc] != '{':
                    print('IR-ERROR: JSON object ({) expected')
                    sys.exit(1)

                # Initialize our nesting level and the starting index of the current object
                nesting = 0
                object_start = json_loc

                # Loop over the characters in the buffer until we find the end of the object.
                while json_loc < buffer_size:
                    # For every { increase nesting, } decrease nesting
                    # The first character should be a { so nesting should start
                    # at 1 after this block
                    if json_buffer[json_loc] == '{':
                        nesting = nesting+1
                    elif json_buffer[json_loc] == '}':
                        nesting = nesting-1

                    # When nesting is 0 we have found as many } as we see {. This means
                    # we have found a full JSON object
                    if nesting == 0:
                        # Convert the string starting at 0 to location+1. Location is the
                        # location of the } character in this case.
                        object_string = json_buffer[object_start:json_loc+1]
                        json_dict = json.loads(object_string)
                        #json_dict['cell_id'] = json_dict['cell_id'] + '-' + json_dict[field] + '-' + json_dict['data_processing_id'] + '-' + json_dict['sample_processing_id']

                        # Keep track of the largest object size to help with buffer management
                        str_len = len(object_string)
                        if str_len > object_str_size:
                            object_str_size = str_len

                        # If we don't have a key for the field of interest in our count,
                        # create it and set it to 0
                        if not json_dict[field] in repertoire_property_count:
                            repertoire_property_count[json_dict[field]] = 0
                        # If we don't have a key for the field of interest in json array,
                        # create it and set it to an empty array.
                        if not json_dict[field] in repertoire_cell_json_array:
                            repertoire_cell_json_array[json_dict[field]] = []
                        # If we don't have a key for the field of interest in our cell_id array,
                        # create it and set it to an empty array.
                        if not json_dict[field] in repertoire_cell_id_array:
                            repertoire_cell_id_array[json_dict[field]] = []

                        # Add the JSON object to the array for this repertoire
                        repertoire_cell_json_array[json_dict[field]].append(json_dict)
                        # Check to see if we have this cell_id for this repertoire
                        if not json_dict['cell_id'] in repertoire_cell_id_array[json_dict[field]]:
                            repertoire_cell_id_array[json_dict[field]].append(json_dict['cell_id'])
                        # And increment the could for this repertoire.
                        repertoire_property_count[json_dict[field]] = repertoire_property_count[json_dict[field]] + 1

                        # Strip off the object we just processed from the buffer as
                        # well as whitespace
                        json_loc = skipWhite(json_buffer, json_loc+1)

                        # Check if the next character is the array end character. If so we are
                        # done.
                        if json_buffer[json_loc] == ']':
                            # Strip off the ] and whitespace
                            json_loc = skipWhite(json_buffer, json_loc+1)
                            # Check to see if we have a closing object character. If not
                            # things are not well formed JSON
                            if json_buffer[json_loc] != '}':
                                print('IR-ERROR: JSON close object expected')
                                sys.exit(1)

                            # Keep track of the fact that we are done (exit the loop).
                            processing_object = False
                            # Break out of the buffer character processing loop.
                            break
                        elif json_buffer[json_loc] != ',':
                            # If the current character isn't a ] it must be a , separator
                            print('IR-ERROR: JSON object separator expected')
                            sys.exit(1)

                        # If we get here we have another object to process. We strip off
                        # the , separator. The next character should therefore be a {
                        # to start the new object, which is what we want.
                        json_loc = skipWhite(json_buffer, json_loc+1)

                        # Finally break out of the character processing loop, as we want
                        # to start over with a new character processing loop for the
                        # the new object with the current buffer.
                        break
                    else:
                        # We processed another character, keep track of where we are
                        # in the string
                        json_loc = json_loc + 1

        # We are done. Check to see if we have any data in the last cell_array,
        # if so create an anndata object for it and append it to our anndata array
        # Loop over the data we have gathered in json array for each repertoire_id
        for my_key in repertoire_cell_json_array:
            if not my_key in repertoire_cell_adata_array:
                repertoire_cell_adata_array[my_key] = []
            if len(repertoire_cell_json_array[my_key]) > 0:
                repertoire_cell_adata_array[my_key].append(createAnnData(repertoire_cell_json_array[my_key], field, my_key))
                print('IR-INFO: reperotire_id = %s, new = %d, total = %d, adata length = %d'% (
                      my_key,
                      len(repertoire_cell_json_array[my_key]),
                      repertoire_property_count[my_key],
                      len(repertoire_cell_adata_array[my_key])),
                      flush=True)

            repertoire_cell_json_array[my_key] = []


        # Concatenate the anndata array into a single object.
        concat_start = time.perf_counter()

        andata_concat = dict()
        for my_key in repertoire_cell_adata_array:
            print('IR-INFO: Concat for %s = %s'%(field, my_key))
            # Get the concatenated object - this will have duplicates.
            ad_concat = anndata.concat(repertoire_cell_adata_array[my_key],
                                            join='outer', merge='first', label='concat_dataset')

            # Determine the cells that are duplicated
            duplicated = ad_concat.obs.index.duplicated(keep='first')
            # Get the list of cell names for the duplicated cells
            obs_duplicated = ad_concat.obs_names[duplicated]

            # Create an andata structure with no duplicates. Note each record
            # with a duplicate will have a partial record in this data set. We
            # later remove this and the duplicate record.
            adata_nodup = ad_concat[~duplicated, :]

            # For each duplicated cell, we create a andata structure that contains
            # the sum of the observations from the partial cell record.
            cell_sum_dict = dict()
            for obs in obs_duplicated:
                # Get a data frame for the partial records for this cell observation.
                cell_df = ad_concat[obs,:].to_df()
                # Sum the observations across the partial records.
                cell_sum = cell_df.sum(axis=0)
                # Create an andata object that is the complete record for this cell.
                cell_sum_dict[obs] = anndata.AnnData(pandas.DataFrame([cell_sum.values], columns=cell_sum.index, index=[obs]),dtype=numpy.float64)
                print('IR-INFO: Computing complete record for Cell ID = %s'%(obs))

            # Now that we have a sum for each record, we need to clean up the partials,
            # and add in the full record.
            for obs in obs_duplicated:
                # Get the list of cell_ids we want to keep. All except the current obs
                no_obs_list = [name for name in adata_nodup.obs_names if not name == obs]
                # Select those cells we want to keep.
                adata_nodup = adata_nodup[no_obs_list, : ]
                # Add the computed full record for the cell we computed above.
                adata_nodup = anndata.concat([adata_nodup,cell_sum_dict[obs]])

            # Keep track of the record with no duplicates.
            andata_concat[my_key] = adata_nodup
            # This should not be necessary, but just in case, we remove duplicates, as 
            # most tools will fail if there are duplicate cell_ids.
            andata_concat[my_key].obs_names_make_unique()
            print('IR-INFO: Length of adata_array = %d'%(len(repertoire_cell_adata_array[my_key])))
            print('IR-INFO: Number of total properties = %d'%(repertoire_property_count[my_key]))
            print('IR-INFO: Number of unique properties = %d'%(len(andata_concat[my_key].var_names)))
            print('IR-INFO: Number of unique cells = %d (%d)'%(
                   len(andata_concat[my_key].obs_names),
                   len(repertoire_cell_id_array[my_key])))

    # Handle generic exceptions.
    except Exception as e:
        raise e
        print('ERROR: Unable to read JSON file %s'%(gex_filename))
        print('ERROR: Reason =' + str(e))
        sys.exit(1)

    # Return the conctatnated AnnData object.
    return andata_concat
    
def getArguments():
    # Set up the command line parser
    parser = argparse.ArgumentParser(
        formatter_class=argparse.RawDescriptionHelpFormatter,
        description=""
    )
    parser = argparse.ArgumentParser()

    # The GEX file to process
    parser.add_argument("airr_gex_file")
    # The directory to write the files to.
    parser.add_argument("output_dir")
    # JSON key for the array that we want to process
    parser.add_argument("block")
    # The field on which we want to filter
    parser.add_argument("field")
    # The value for the field that we are filtering
    #parser.add_argument("value")

    # Request normaliztion of the counts for each cell to the given value.
    parser.add_argument(
        "--normalize",
        action="store_true",
        help="Request each cell count to be normalized")

    # Request normaliztion of the counts for each cell to the given value.
    parser.add_argument(
        "--normalize_value",
        dest="normalize_value",
        type=float,
        default=10000.0,
        help="Request each cell count to be normalized to this value")

    # Perform logarithmic processing - as required by some tools (celltypist)
    parser.add_argument(
        "--log1p",
        dest="log1p",
        action="store_true",
        help="Request the data to be transformed to a logarithmic representation.")

    # The blocksize to be used
    parser.add_argument(
        "--blocksize",
        dest="blocksize",
        type=int,
        default=100*1024*1024,
        help="Size of the block to process at each step. Determines the memory footprint for the application")

    # Verbosity flag
    parser.add_argument(
        "-v",
        "--verbose",
        action="store_true",
        help="Run the program in verbose mode.")

    # Parse the command line arguements.
    options = parser.parse_args()
    return options

if __name__ == "__main__":
    total_start = time.perf_counter()
    # Get the command line arguments.
    options = getArguments()

    # Generate an H5AD file from the GEX file.
    adata_dict = generateH5AD(options.airr_gex_file, options.block,
                              options.field, options.blocksize)

    # Check for a good conversion.
    if adata_dict is None: 
        print('ERROR: Unable to process AIRR GEX file %s'%(options.airr_gex_file))
        sys.exit(1)

    # For each repertoire, process as required by the command line options.
    for my_key in adata_dict:
        adata = adata_dict[my_key]

        # If normalization requested, normalize. Default normalize to 1.
        if options.normalize:
            print("IR-INFO: Normalizing cell counts to %f"%(options.normalize_value))
            scanpy.pp.normalize_total(adata, target_sum=options.normalize_value)
    
        # If log scaling requested, do it.
        if options.log1p:
            print("IR-INFO: Performing log scaling" )
            scanpy.pp.log1p(adata)
            # scanpy has a bug, doesn't save "None" data into files.
            # log1p sets base = None, so we want to change it toe numpy.e
            adata.uns["log1p"]["base"]=numpy.e
    
        # Write the output to the output file.
        output_file = my_key + '.h5ad'
        adata.write(options.output_dir + '/' + output_file )
        print("IR-INFO: Done writing file %s"%(output_file))
        print(adata.to_df())

    # Print out timing
    total_end = time.perf_counter()
    print('IR-INFO: Total time = %d s'%(total_end-total_start))
    # Return success if successful
    sys.exit(0)
