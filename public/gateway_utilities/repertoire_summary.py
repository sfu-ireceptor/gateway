import sys
import argparse
import json

# Extract a representative set of information about a repertoire. Outputs the
# list of fields separated by the specified seperator.
def repertoireSummary(json_filename, repertoire_id, verbose, separator=", "):

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

    # Check for a correct Repertoire object.
    repertoire_key = "Repertoire"
    if not repertoire_key in json_data:
        print("ERROR: Expected to find a 'Repertoire' object, none found")
        return False

    # Find the repertoire of interest and print out relevant fields.
    for repertoire in json_data[repertoire_key]:
        if repertoire['repertoire_id'] == repertoire_id:
            # Assign default values
            study_id = 'None'
            subject_id = 'None'
            sample_id = 'None'
            tissue = 'None'
            pcr_target_locus = 'None'

            # Get the study ID if it exists
            if 'study' in repertoire and 'study_id' in repertoire['study']:
                study_id = repertoire['study']['study_id']

            # Get the subject ID if it exists
            if 'subject' in repertoire and 'subject_id' in repertoire['subject']:
                subject_id = repertoire['subject']['subject_id']

            # Get the sample info if it exists.
            if 'sample' in repertoire:
                sample_list = repertoire['sample']
                if isinstance(sample_list, list):
                    sample_obj = sample_list[0]
                    if 'sample_id' in sample_obj:
                        sample_id = sample_obj['sample_id']
                    if 'tissue' in sample_obj and 'label' in sample_obj['tissue']:
                        tissue = sample_obj['tissue']['label']
                    if 'pcr_target' in sample_obj:
                        if isinstance(sample_obj['pcr_target'], list) and 'pcr_target_locus' in sample_obj['pcr_target'][0]:
                            pcr_target_locus = sample_obj['pcr_target'][0]['pcr_target_locus']

            print("%s%s%s%s%s%s%s%s%s"%(
              study_id, separator, subject_id, separator,
              sample_id, separator, tissue, separator, pcr_target_locus
              ))
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

