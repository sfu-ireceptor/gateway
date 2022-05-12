import argparse
import sys
import pandas

def getArguments():
    parser = argparse.ArgumentParser(
        formatter_class=argparse.RawDescriptionHelpFormatter,
        description=""
    )

    # Filename to process
    parser.add_argument("filename")
    # Field name to filter on
    parser.add_argument("field_name")
    # Field value on which to filter.
    parser.add_argument("field_value")
    # Output file to use.
    parser.add_argument("outfile")

    options = parser.parse_args()
    return options


# A simple program to filter an AIRR TSV file based on an AIRR field. 
# Records are not changed, but only those records that contain
# field_value in field_name are store in the output file.
if __name__ == "__main__":

    # Get the arguments
    options = getArguments()

    # Set a chunk size to step through the data.
    chunk_size = 100000

    # Create file reader.
    try:
        airr_df_reader = pandas.read_csv(options.filename, sep='\t', chunksize=chunk_size, dtype= {'repertoire_id': str, 'data_processing_id': str})
    except Exception as e:
        print('ERROR: Unable to read TSV file %s'%(options.filename))
        print('ERROR: Reason =' + str(e))
        sys.exit(1)

    print('Searching field %s for value %s'%(options.field_name, options.field_value))
    chunk_count = 0
    total_size = 0
    # Loop over the file a chunk at a time.
    for airr_df in airr_df_reader:
        # If the field name is in the data frame, process further
        if options.field_name in airr_df:
            # Slice the data on the field name containg the value of interest.
            #print("Processing record %d"%(chunk_count*chunk_size))
            field_df = airr_df.iloc[list(airr_df[options.field_name] == str(options.field_value)), :]
            #print("Writing %d records"%(field_df.index.size))
            # Write the data to the output file.
            if chunk_count == 0:
                field_df.to_csv(options.outfile, sep='\t', mode='a', header=True, index=False)
            else:
                field_df.to_csv(options.outfile, sep='\t', mode='a', header=False, index=False)
            # Keep track of how much data we processed.
            chunk_count = chunk_count + 1
            total_size = total_size + field_df.index.size
        else:
            print("Warning: Field " + options.field_name + " not in file " + options.filename)

    
    # Print out some info about what was processed.
    if total_size == 0:
        print("Warning: Could not find any data for " + options.field_name + " = " + options.field_value)
    print("Wrote %d records for %s = %s"%(total_size, options.field_name, options.field_value))
