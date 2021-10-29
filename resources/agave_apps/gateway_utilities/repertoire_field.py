import sys
import argparse
import json

# Extract a representative set of information about a repertoire. Outputs the
# list of fields separated by the specified seperator.
def repertoireField(json_filename, repertoire_id, repertoire_field, verbose):

    # Open the file.
    try:
        with open(json_filename) as f:
            json_data = json.load(f)
    except Exception as e:
        print('ERROR: Unable to read JSON file %s'%(json_filename))
        print('ERROR: Reason =' + str(e))
        return []

    # Print out an error if the query failed.
    if len(json_data) == 0:
        print('ERROR: JSON file load for %s failed '%(json_filename))
        return []

    # Check for a correct Info object.
    if not "Info" in json_data:
        print("ERROR: Expected to find an 'Info' object, none found")
        return []

    # Check for a correct Repertoire object.
    repertoire_key = "Repertoire"
    if not repertoire_key in json_data:
        print("ERROR: Expected to find a 'Repertoire' object, none found")
        return []
    repertoire_array = json_data[repertoire_key]

    # Split the field string on the "." delimeter.
    field_array = repertoire_field.split(".")

    # Find the repertoire of interest and print out relevant fields.
    for repertoire in json_data[repertoire_key]:
        # Check to make sure we have a repertoire_id tag.
        if not "repertoire_id" in repertoire:
            print("ERROR: Could not find repertoire_id tag in Repertoire.")
            return []
        if (not repertoire_id == None && repertoire['repertoire_id'] == repertoire_id):
            current_object = repertoire
            last_field = "repertoire"
            for field in field_array:
                if not field in current_object:
                    print("ERROR: Could not find %s tag in %s object."%(field, last_field))
                    return []

                current_object = current_object[field]
                print(field)
                last_field = field

def getArguments():
    # Set up the command line parser
    parser = argparse.ArgumentParser(
        formatter_class=argparse.RawDescriptionHelpFormatter,
        description=""
    )
    parser = argparse.ArgumentParser()

    # The filename to use
    parser.add_argument("--json_filename", required=True)
    # The repertoire field to extract in "dot" notation
    # The "subject_id" field is within the "Subject" object in the AIRR Spec,
    # identified with the "subject" key so to extract "subject_id" use "subject.subject_id"
    # This is similar to the ADC API query protocol.
    parser.add_argument("--repertoire_field", required=True)
    # The repertoire_id to summarize
    parser.add_argument("--repertoire_id", required=True)
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
    success = repertoireField(options.json_filename, options.repertoire_id,
                              options.repertoire_field, options.verbose)

    # Return success if successful
    if not success:
        sys.exit(1)

