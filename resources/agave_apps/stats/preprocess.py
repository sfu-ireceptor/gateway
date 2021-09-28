# Imports
import sys
import pandas
import argparse

def getArguments():
    parser = argparse.ArgumentParser(
        formatter_class=argparse.RawDescriptionHelpFormatter,
        description=""
    )

    # API x and y fields to use
    parser.add_argument("filename")
    parser.add_argument("field")

    options = parser.parse_args()
    return options


# A simple program that takes a TSV file and extracts the specified field.
if __name__ == "__main__":

    # Get the arguments
    options = getArguments()

    # Specify the size of the chunk of data to process at a time
    chunk_size = 100000

    # Create a reader for the TSV file, readding chunk_size records per step.
    try:
        airr_df_reader = pandas.read_csv(options.filename, sep='\t', chunksize=chunk_size)

        # Loop over the file until donw
        for airr_df in airr_df_reader:
            # If the field in the data frame
            if options.field in airr_df:
                # Extract the field and then print out those field values.
                field_df = airr_df.loc[: ,options.field]
                for value in field_df:
                    print(value)
    except Exception as e:
        print('ERROR: Unable to read TSV file %s'%(options.filename))
        print('ERROR: Reason =' + str(e))
        sys.exit(1)

    sys.exit(0)
