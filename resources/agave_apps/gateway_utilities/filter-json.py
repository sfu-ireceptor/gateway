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
        start_pos = 0
        file_loc = 0
        buffer_size = 10000
        buffer_loc = 0
        with open(options.filename, 'r') as f:
            json_str = f.read(buffer_size)
            json_str = json_str.replace('\n',' ')
            file_loc = buffer_size
            json_str = json_str.strip()
            json_len = len(json_str) 

            if json_str[0] != '{':
                print('ERROR: JSON file %s has no opening {'%(options.filename))
                sys.exit(1)
            print('found {')
            json_str = json_str[1:].strip()

            if json_str[0:len(options.block)+2] != '"%s"'%(options.block):
                print('ERROR: Could not find key "%s" in JSON file %s'%(options.block, options.filename))
                sys.exit(1)
            print('found %s'%(options.block))

            json_str = json_str[len(options.block)+2+1:].strip()
            if json_str[0] != '[':
                print('ERROR: JSON object %s is not an array'%(options.block))
                sys.exit(1)
            print('found [')

            json_str = json_str[1:].strip()
            processing_object = True
            while processing_object:
                if json_str[0] != '{':
                    print('ERROR: JSON object expected'%(options.block))
                    sys.exit(1)
                print('found {')
                nesting = 0
                location = 0
                count = 0
                for character in json_str:
                    # For ever { increase nesting, } decrease nesting
                    # The first character should be a {
                    if character == '{':
                        nesting = nesting+1
                    elif character == '}':
                        nesting = nesting-1

                    # We have found a full JSON object
                    if nesting == 0:
                        #print(json_str[:location+1])
                        json_dict = json.loads(json_str[:location+1])
                        #print(json_dict)
                        if options.field in json_dict:
                            if json_dict[options.field] == options.value:
                                if ( count > 0 ):
                                    print(',')
                                print(json.dumps(json_dict))
                                count = count + 1

                        json_str = json_str[location+1:].strip()
                        if json_str[0] == ']':
                            print('Array ended')
                            json_str = json_str[1:].strip()
                            if json_str[0] != '}':
                                print('ERROR: JSON close object expected')
                                sys.exit(1)
                            print('found }')
                            processing_object = False
                            break
                        elif json_str[0] != ',':
                            print('ERROR: JSON object separator expected')
                            sys.exit(1)
                        print('found ,')

                        json_str = json_str[1:].strip()
                        break
                    else:
                        # Keep track of where we are in the string
                        location = location + 1

            print('Done')
            sys.exit(0)



#            while True:
#                try:
#                    f.seek(start_pos)
#                    json_str = f.read(start_pos+buffer_size)
#                    obj = json.load(f)
                    #yield obj
#                    return
#            except json.JSONDecodeError as e:
#                obj = json.loads(json_str)
#                start_pos += e.pos
##                yield obj
#            json_dict = json.load(f)
    except Exception as e:
        print('ERROR: Unable to read JSON file %s'%(options.filename))
        print('ERROR: Reason =' + str(e))
        raise
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
