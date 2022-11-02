# Imports
import sys
import time
import string
import argparse
import json

def getArguments():
    parser = argparse.ArgumentParser(
        formatter_class=argparse.RawDescriptionHelpFormatter,
        description=""
    )

    # Input file name to process
    parser.add_argument("input_filename")
    # JSON key for the array that we want to process
    parser.add_argument("block")
    # The field on which we want to filter
    parser.add_argument("field")
    # The value for the field that we are filtering
    parser.add_argument("value")
    # The output file to use.
    parser.add_argument("output_filename")
    # The blocksize to be used
    parser.add_argument(
        "--blocksize",
        dest="blocksize",
        type=int,
        default=100*1024*1024,
        help="Size of the block to process at each step. Determines the memory footprint for the application")

    options = parser.parse_args()
    return options

# Skip any white space in the buffer, starting at index str_loc
# Return: the index of the first non-white space character.
def skipWhite(str_buffer, str_loc):
    while str_buffer[str_loc] in string.whitespace:
        str_loc = str_loc + 1
    return str_loc

# A simple program that takes a JSON file and extracts the specified field.
# Assumes that the JSON file is an AIRR file, and has a typical AIRR JSON
# structure with an Info block and then one of the AIRR JSON array blocks
# for either Cells/Expression/Repertoire, etc. The code acually does not
# check the JSON file structure up to the block of interest, and assumes 
# correct JSON. This is NOT a JSON syntax checker, use another tool if you
# need that capability.
if __name__ == "__main__":

    # Get the arguments
    options = getArguments()

    try:
        # Set the string buffer size.
        buffer_size = options.blocksize
        # Open the input and output files.
        with open(options.input_filename, 'r') as file_in:
          with open(options.output_filename, 'w') as file_out:

            # Read in our first buffer_size characters.
            t_start = time.perf_counter()
            json_buffer = file_in.read(buffer_size)
            json_loc = 0


            # Strip of any white space in preparation for processing.
            json_loc = skipWhite(json_buffer, json_loc)

            # AIRR JSON files are JSON objects
            if json_buffer[json_loc] != '{':
                print('IR_ERROR: JSON file %s has no opening {'%(options.input_filename))
                sys.exit(1)

            # Look at the next part of the string, excluding white space.
            json_loc = skipWhite(json_buffer, json_loc+1)

            # We look for the block key in the string (enclosed in quotes of course)
            # If we can't find it in the first buffer_size characters we assume it isn't there.
            # We are cheating here - as we assume everything between the opening { for
            # the object and the block key we are interested in is correct JSON. 
            json_loc = json_buffer.find('"%s"'%(options.block), json_loc)
            if json_loc == -1:
                print('IR_ERROR: Could not find key "%s" in first %d characters of JSON file %s'%(options.block, buffer_size, options.input_filename))
                sys.exit(1)

            # Strip off the block key and any new white space
            json_loc = skipWhite(json_buffer, json_loc+len(options.block)+2)
            
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

            # If we got this far, we have the correct format for a JSON AIRR expression file.
            # Print the JSON header for the file, essentially duplicating what we just read.
            print('{', file=file_out)
            print('"%s":['%(options.block), file=file_out)
            
            # Initalize our loop variables. We track whether we are still processing objects,
            # the maximum size of the objects in the array we are processing, the threshold that
            # determines how many objects we hold in the buffer before reading more data,
            # a flag to note whether the file is done processing, and a count of how many
            # objects we have written.
            processing_object = True
            object_str_size = 0
            object_threshold = 4
            file_empty = False
            count = 0
            while processing_object:
                # Check to see if the buffere is almost consumed. If we have room
                # for less than N objects, then read in more data. Don't read any more 
                # data if the file is empty.
                buffer_remaining = buffer_size - json_loc

                if buffer_remaining < object_threshold*object_str_size and not file_empty:
                    t_end = time.perf_counter()
                    print('IR-INFO: expression count = %d (%d s)'%
                         (count,t_end-t_start), flush=True)

                    # Read in the new data
                    t_start = time.perf_counter()
                    new_buffer = file_in.read(buffer_size)
                    # If we are at file end, note it, otherwise add it on to the json string
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

                # Initialize our nesting level and the location in the string.
                nesting = 0
                location = 0

                # Loop over the characters in the string.
                object_start = json_loc
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

                        # Keep track of the larges object size to help with buffer management
                        str_len = len(object_string)
                        if str_len > object_str_size:
                            object_str_size = str_len

                        # If the field of interest is in the dictionary
                        if options.field in json_dict:
                            # and if the field has the value if interest
                            if json_dict[options.field] == options.value:
                                # Print out the object seperator (if after the first object) and
                                # then proint the dictionary as JSON (with no newline).
                                if count > 0:
                                    print(',', file=file_out)
                                print(json.dumps(json_dict), end='', file=file_out, flush=True)
                                count = count + 1

                        # Strip off the object we just processed from the buffer as
                        # well as whitespace
                        json_loc = skipWhite(json_buffer, json_loc+1)

                        # Check if the next character is the array end character. If so we are
                        # done.
                        if json_buffer[json_loc] == ']':
                            # Strip off the ] and whitespace
                            json_loc = skipWhite(json_buffer, json_loc+1)

                            # Check to see if we have a closing object character. If not things
                            # are not well formed JSON
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

                        # If we get here we have another object to process. We strip off the ,
                        # separator. The next character should therefore be a { to start the
                        # new object, which is what we want.
                        json_loc = skipWhite(json_buffer, json_loc+1)

                        # Finally break out of the character processing loop, as we want to
                        # start over with the new object processing with the current buffer.
                        break
                    else:
                        # Keep track of where we are in the string
                        json_loc = json_loc + 1

            # Print out the JSON closing array and object characters so we have valid JSON.
            print(']', file=file_out)
            print('}', file=file_out)
            
    # Handle generic exceptions.
    except Exception as e:
        raise e
        print('ERROR: Unable to read JSON file %s'%(options.input_filename))
        print('ERROR: Reason =' + str(e))
        sys.exit(1)

    # Exit with success!
    sys.exit(0)
            


