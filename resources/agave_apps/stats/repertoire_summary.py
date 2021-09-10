import sys
import argparse
import json

def repertoireSummary(json_filename, repertoire_id, verbose, separator=", "):

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

    for repertoire in json_data[repertoire_key]:
        if repertoire['repertoire_id'] == repertoire_id:
            study_json = repertoire['study']
            subject_json = repertoire['subject']
            sample_json = repertoire['sample']
            print("%s%s%s%s%s%s%s%s%s"%(
              study_json['study_id'], separator,
              subject_json['subject_id'], separator,
              sample_json[0]['sample_id'], separator,
              sample_json[0]['tissue']['label'], separator,
              sample_json[0]['pcr_target'][0]['pcr_target_locus']
              ))
        

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
    parser.add_argument("repertoire_id")
    # Separator between fields
    parser.add_argument("--separator", default=", ")
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
    success = repertoireSummary(options.json_filename, options.repertoire_id,
                                options.verbose, options.separator)

    # Return success if successful
    if not success:
        sys.exit(1)

