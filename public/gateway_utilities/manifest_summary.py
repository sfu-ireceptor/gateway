import sys
import argparse
import json

# Extract fields of choice from an AIRR manifest file. Outputs the
# list of fields separated by the specified seperator.
def manifestSummary(json_filename, field_name, verbose, separator=" "):

    # Open the file.
    try:
        with open(json_filename) as f:
            json_data = json.load(f)
    except Exception as e:
        print('ERROR: Unable to read JSON file %s'%(json_filename))
        print('ERROR: Reason =' + str(e))
        return False

    # Print out an error if the query failed.
    if len(json_data) == 0:
        print('ERROR: JSON file load for %s failed '%(json_filename))
        return False

    # Check for a correct Info object.
    if not "Info" in json_data:
        print("ERROR: Expected to find an 'Info' object, none found")
        return False

    # Check for a correct Info object.
    if not "DataSets" in json_data:
        print("ERROR: Expected to find a 'DataSets' object, none found")
        return False

    # Iterate over the datasets.
    for dataset in json_data['DataSets']:
        # If the filed of interest is available in the data set process it.
        if field_name in dataset:
            # If we have an array, output all values from the list. If not, 
            # then just output the value of the field.
            if isinstance(dataset[field_name], list):
                for field_value in dataset[field_name]:
                    print("%s%s"%(field_value, separator),end="")
            else:
                print("%s%s"%(str(dataset[field_name]), separator),end="")
    return True
        

def getArguments():
    # Set up the command line parser
    parser = argparse.ArgumentParser(
        formatter_class=argparse.RawDescriptionHelpFormatter,
        description=""
    )
    parser = argparse.ArgumentParser()

    # The filename to use
    parser.add_argument("json_filename")
    # The repertoire_id to summarize
    parser.add_argument("dataset_field")
    # Separator between fields
    parser.add_argument("--separator", default=" ")
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
    # Get the command line arguments.
    options = getArguments()

    # Get the repertoire summary list of information
    success = manifestSummary(options.json_filename, options.dataset_field,
                              options.verbose, options.separator)

    # Return success if successful
    if not success:
        sys.exit(1)

