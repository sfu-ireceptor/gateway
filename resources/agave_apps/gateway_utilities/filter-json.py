# Imports
import sys
import argparse
import json

def getArguments():
    parser = argparse.ArgumentParser(
        formatter_class=argparse.RawDescriptionHelpFormatter,
        description=""
    )

    # API x and y fields to use
    parser.add_argument("filename")
    parser.add_argument("block")
    parser.add_argument("field")
    parser.add_argument("value")

    options = parser.parse_args()
    return options


# A simple program that takes a JSON file and extracts the specified field.
if __name__ == "__main__":

    # Get the arguments
    options = getArguments()
    # Open the JSON file.
    try:
        with open(options.filename) as f:
            json_dict = json.load(f)
    except Exception as e:
        print('ERROR: Unable to read JSON file %s'%(options.filename))
        print('ERROR: Reason =' + str(e))
        sys.exit(1)

    # Check to see if the block exists.
    if options.block in json_dict:
        print('{')
        print('"%s":['%(options.block))
        # If it does, check that it is an array
        if isinstance(json_dict[options.block], list):
            # If it is, iterate over the array
            count = 0
            for data_object in json_dict[options.block]:
                # If the field of interest is in the object, print its value.
                if options.field in data_object:
                    if data_object[options.field] == options.value:
                        if ( count > 0 ):
                            print(',')
                        print(json.dumps(data_object))
                        count = count + 1
        else:
            print('ERROR: Block object %s in file %s is not an array'%(options.block, options.filename))
            sys.exit(1)
        print(']')
        print('}')
    else:
        print('ERROR: Could not find block %s in file %s'%(options.block, options.filename))
        sys.exit(1)

    sys.exit(0)
