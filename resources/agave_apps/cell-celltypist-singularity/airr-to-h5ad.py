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
    # Get a data frame from the array of cells dictionary
    print('IR-INFO: CreateAnnData - creating pandas data frame %d'%(time.perf_counter()), flush=True)
    df = pandas.DataFrame.from_records(cell_dict_array)

    # Get the unique cells
    cells = df["cell_id"].unique()
    print('IR-INFO: number of cells = %d'%(len(cells)), flush=True)
    
    # Get the unique property labels and IDs. 
    property_dict_array = df["property"]
    label_series = pandas.Series([d['label'] for d in property_dict_array])
    id_series = pandas.Series([d['id'] for d in property_dict_array])
    property_labels = label_series.unique()
    property_ids = id_series.unique()

    # Get the shape of our object (cells x properties)
    shape = (len(cells), len(property_labels))
    #shape = (len(cells), len(property_ids))
    
    # Create categorical cells and properties
    cell_cat = CategoricalDtype(cells)
    property_cat = CategoricalDtype(property_labels)
    #property_cat = CategoricalDtype(property_ids)

    # Create our indexes 
    cell_index = df["cell_id"].astype(cell_cat).cat.codes
    property_index = label_series.astype(property_cat).cat.codes
    #property_index = id_series.astype(property_cat).cat.codes

    # Create the sparse matrix
    print('IR-INFO: Creating coo matrix %d'%(time.perf_counter()), flush=True)
    coo = sparse.coo_matrix((df["value"], (cell_index, property_index)), shape=shape)
    print('IR-INFO: Creating csr matrix %d'%(time.perf_counter()), flush=True)
    csr = coo.tocsr()

    # Create the AnnData object from the sparse matrix
    print('IR-INFO: Creating adata %d'%(time.perf_counter()), flush=True)
    adata = anndata.AnnData(csr, dtype=numpy.float64)

    # Assign some useful observation attributes (cell_id, partition field, partition value)
    print('IR-INFO: Assigning attributes %d'%(time.perf_counter()), flush=True)
    adata.obs['cell_id'] = cells
    adata.obs['field'] = field
    adata.obs['value'] = value

    # Assign some useful variable attributes (property.id and property.label)
    # If the number of IDs is different than the number of labels, then we have
    # a ontology ID/label conflict, so don't assign the labels and print a warning.
    adata.var['property.label'] = property_labels
    #print(adata.var['property.label'])
    if len(property_labels) == len(property_ids):
        adata.var['property.id'] = property_ids
        #print(adata.var['property.id'])
    else:
        print('IR-INFO: property labels and ids do not match, not storing IDs')

    # Assign the observation and variable names (cell_id and property.id)
    adata.obs_names = cells
    adata.var_names = property_labels
    #adata.var_names = property_ids

    # Make the var names unique
    #adata.var_names_make_unique()

    # Return the data structure.
    # print(adata.to_df())
    print('IR-INFO: Finished createAnnData %d'%(time.perf_counter()), flush=True)
    return adata

def skipWhite(str_buffer, str_loc):
    while str_buffer[str_loc] in string.whitespace:
        str_loc = str_loc + 1
    return str_loc

def mystrip(input_string):
    # Strip of any white space 
    count = 0
    for character in input_string:
        if not character in string.whitespace:
            break
        count = count + 1
    return input_string[count:]

def generateH5AD(gex_filename, block, field, value):
    # Keep track of the number of properties
    count = 0
    # An array to hold the anndata objects in
    adata_array = []
    # Open the JSON file.
    try:
        buffer_size = 1*1024*1024*1024
        buffer_size = 100*1024*1024
        #buffer_size = 10*1024*1024
        slow = False
        with open(gex_filename, 'r') as f:

            # Read in our first buffer_size characters.
            t_start = time.perf_counter()
            json_buffer = f.read(buffer_size)
            json_loc = 0
            #json_str = json_buffer
            #print(id(json_buffer))
            #print(id(json_str))

            #count = 0
            #for character in json_str:
            #    if not character in string.whitespace:
            #        break
            #    count = count + 1
            #my_str = json_str[count:]
            #print(id(my_str))
            # Strip of any white space in preparation for processing.
            #json_str = json_str.lstrip()
            #json_str = mystrip(json_str) if slow == False else json_str.lstrip()
            #print(id(json_str))
            json_loc = skipWhite(json_buffer, json_loc)

            # AIRR JSON files are JSON objects
            #if json_str[0] != '{':
            if json_buffer[json_loc] != '{':
                print('IR_ERROR: JSON file %s has no opening {'%(gex_filename))
                sys.exit(1)
            # Look at the next part of the string, excluding white space.
            #json_str = json_str[1:].lstrip()
            #json_str = mystrip(json_str[1:]) if slow == False else json_str[1:].lstrip()
            json_loc = skipWhite(json_buffer, json_loc+1)

            # We look for the block key in the string (enclosed in quotes of course)
            # If we can't find it in the first buffer_size characters we assume it isn't there.
            # We are cheating here - as we assume everything between the opening { for
            # the object and the block key we are interested in is correct JSON.
            #block_loc = json_str.find('"%s"'%(block))
            json_loc = json_buffer.find('"%s"'%(block), json_loc)

            #if block_loc == -1:
            if json_loc == -1:
                print('IR_ERROR: Could not find key "%s" in first %d characters of JSON file %s'%(block, buffer_size, gex_filename))
                sys.exit(1)

            # Strip off the block key and any new white space
            #json_str = json_str[block_loc+len(block)+2:].lstrip()
            #json_str = mystrip(json_str[block_loc+len(block)+2:]) if slow == False else json_str[block_loc+len(block)+2:].lstrip()
            json_loc = skipWhite(json_buffer, json_loc+len(block)+2)

            # The key should be followed by a separator :
            #if json_str[0] != ':':
            if json_buffer[json_loc] != ':':
                print('IR_ERROR: expected JSON : separator')
                sys.exit(1)
            # Strip off the array character and any white space.
            #json_str = json_str[1:].lstrip()
            #json_str = mystrip(json_str[1:]) if slow == False else json_str[1:].lstrip()
            json_loc = skipWhite(json_buffer, json_loc+1)

            # The block should be an array, so we expect the array character.
            #if json_str[0] != '[':
            if json_buffer[json_loc] != '[':
                print('IR_ERROR: JSON object %s is not an array'%(block))
                sys.exit(1)
            # Strip off the array character and any white space.
            #json_str = json_str[1:].lstrip()
            #json_str = mystrip(json_str[1:]) if slow == False else json_str[1:].lstrip()
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
            cell_array = []
            while processing_object:
                # Check to see if the buffere is almost consumed. If we have room
                # for less than N objects, then read in more data. Don't read any more
                # data if the file is empty.
                #buffer_remaining = len(json_str)
                buffer_remaining = buffer_size - json_loc
                if buffer_remaining < object_threshold*object_str_size and not file_empty:
                    #print('we are almost out of buffer')
                    #print('size = %d, remaing = %d, obj_size = %s'%(buffer_size,buffer_remaining,object_str_size))
                    #print('length of array = %d'%(len(cell_array)))

                    adata_array.append(createAnnData(cell_array, field, value))
                    t_end = time.perf_counter()
                    print('IR-INFO: property count = %d, array length = %d (%d s)'%
                         (count,len(adata_array),t_end-t_start), flush=True)

                    cell_array = []
                    # Read in the new data
                    t_start = time.perf_counter()
                    new_buffer = f.read(buffer_size)
                    # If we are at file end, note it, otherwise add it on to the json string
                    if len(new_buffer) == 0:
                        file_empty = True
                    else:
                        #json_str = json_str + new_buffer
                        json_buffer = json_buffer[json_loc:] + new_buffer
                        json_loc = 0
                        #print(json_buffer)
                # Check to make sure that the first charater is an object delimeter. We have run
                # strip to get rid of any whitespace so anything else is an error.
                #if json_str[0] != '{':
                if json_buffer[json_loc] != '{':
                    print('IR-ERROR: JSON object expected'%(block))
                    sys.exit(1)

                # Initialize our nesting level and the location in the string.
                nesting = 0
                location = 0

                # Loop over the characters in the string.
                #for character in json_str:
                object_start = json_loc
                while json_loc < buffer_size:
                    # For every { increase nesting, } decrease nesting
                    # The first character should be a { so nesting should start
                    # at 1 after this block
                    #if character == '{':
                    if json_buffer[json_loc] == '{':
                        nesting = nesting+1
                    #elif character == '}':
                    elif json_buffer[json_loc] == '}':
                        nesting = nesting-1

                    # When nesting is 0 we have found as many } as we see {. This means
                    # we have found a full JSON object
                    if nesting == 0:
                        # Convert the string starting at 0 to location+1. Location is the
                        # location of the } character in this case.
                        #json_dict = json.loads(json_str[:location+1])
                        object_string = json_buffer[object_start:json_loc+1]
                        #print(object_string)
                        json_dict = json.loads(object_string)
                        # Keep track of the larges object size to help with buffer management
                        #str_len = len(json_str[:location+1])
                        str_len = len(object_string)
                        if str_len > object_str_size:
                            object_str_size = str_len

                        # If the field of interest is in the dictionary
                        if field in json_dict:
                            # and if the field has the value if interest
                            if json_dict[field] == value:
                                # Print out the object seperator (if after the first object) and
                                # then proint the dictionary as JSON (with no newline).
                                #if count > 0:
                                #    print(',')
                                #print(json.dumps(json_dict), end='')
                                cell_array.append(json_dict)
                                count = count + 1
                        # Strip off the object we just processed from the buffer as
                        # well as whitespace
                        #json_str = json_str[location+1:].lstrip()
                        #json_str = mystrip(json_str[location+1:]) if slow == False else json_str[location+1:].lstrip()
                        json_loc = skipWhite(json_buffer, json_loc+1)
                        

                        # Check if the next character is the array end character. If so we are
                        # done.
                        # if json_str[0] == ']':
                        if json_buffer[json_loc] == ']':
                            # Strip off the ] and whitespace
                            #json_str = mystrip(json_str[1:]) if slow == False else json_str[1:].lstrip()
                            json_loc = skipWhite(json_buffer, json_loc+1)
                            # Check to see if we have a closing object character. If not
                            # things are not well formed JSON
                            #if json_str[0] != '}':
                            if json_buffer[json_loc] != '}':
                                print('IR-ERROR: JSON close object expected')
                                sys.exit(1)

                            # Keep track of the fact that we are done (exit the loop).
                            processing_object = False
                            # Break out of the buffer character processing loop.
                            break
                        #elif json_str[0] != ',':
                        elif json_buffer[json_loc] != ',':
                            # If the current character isn't a ] it must be a , separator
                            print('IR-ERROR: JSON object separator expected')
                            sys.exit(1)

                        # If we get here we have another object to process. We strip off
                        # the , separator. The next character should therefore be a {
                        # to start the new object, which is what we want.
                        #json_str = json_str[1:].lstrip()
                        #json_str = mystrip(json_str[1:]) if slow == False else json_str[1:].lstrip()
                        json_loc = skipWhite(json_buffer, json_loc+1)
                        # Finally break out of the character processing loop, as we want
                        # to start over with the new object processing with the current
                        # json_string in the buffer.
                        break
                    else:
                        # Keep track of where we are in the string
                        #location = location + 1
                        json_loc = json_loc + 1
        # We are done. Check to see if we have any data in the last cell_array,
        # if so create an anndata object for it and append it to our array
        if len(cell_array) > 0:
            print('IR-INFO: Adding %d at end of file processing'%(len(cell_array)), flush=True)
            adata_array.append(createAnnData(cell_array, field, value))

        ad_concat = anndata.concat(adata_array, join='outer', merge='first',  label='concat_dataset')
        # WARNING - THIS IS INCORRECT - we need to merge cells if they have the same name
        # NOT rename them!!! DOING THIS FOR DEBUGGING ONLY
        ad_concat.obs_names_make_unique()
        print('IR-INFO: Length of adata_array = %d'%(len(adata_array)))
        #print(adata_array)
        print('IR-INFO: Number of properties = %s'%(count))
        print(ad_concat.to_df())
        #print(ad_concat.obs['cell_id'])
        #print(ad_concat.obs['field'])
        #print(ad_concat.obs['value'])


    # Handle generic exceptions.
    except Exception as e:
        raise e
        print('ERROR: Unable to read JSON file %s'%(gex_filename))
        print('ERROR: Reason =' + str(e))
        sys.exit(1)

    return ad_concat 
    
def getArguments():
    # Set up the command line parser
    parser = argparse.ArgumentParser(
        formatter_class=argparse.RawDescriptionHelpFormatter,
        description=""
    )
    parser = argparse.ArgumentParser()

    # The GEX file to process
    parser.add_argument("airr_gex_file")
    # The h5ad file to write
    parser.add_argument("output_file")
    # JSON key for the array that we want to process
    parser.add_argument("block")
    # The field on which we want to filter
    parser.add_argument("field")
    # The value for the field that we are filtering
    parser.add_argument("value")

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
    adata = generateH5AD(options.airr_gex_file, options.block, options.field, options.value)

    # Return success if successful
    if adata is None: 
        print('ERROR: Unable to process AIRR GEX file %s'%(options.airr_gex_file))
        sys.exit(1)

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
    print("IR-INFO: Writing file %s"%(options.output_file))
    adata.write(options.output_file)
    print("IR-INFO: Done writing file %s"%(options.output_file))
    print(adata.to_df())
    total_end = time.perf_counter()
    print('IR-INFO: Total time = %d s'%(total_end-total_start))

    sys.exit(0)
