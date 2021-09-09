import sys
import pandas

filename = sys.argv[1]
field_name = sys.argv[2]
field_value = sys.argv[3]
outfile = sys.argv[4]
chunk_size = 100000

airr_df_reader = pandas.read_csv(filename, sep='\t', chunksize=chunk_size, dtype= {'repertoire_id': str})
chunk_count = 0
total_size = 0
for airr_df in airr_df_reader:
    if field_name in airr_df:
        print("Processing record %d"%(chunk_count*chunk_size))
        field_df = airr_df.iloc[list(airr_df[field_name] == str(field_value)), :]
        print("Writing %d records"%(field_df.index.size))
        if chunk_count == 0:
            field_df.to_csv(outfile, sep='\t', mode='a', header=True, index=False)
        else:
            field_df.to_csv(outfile, sep='\t', mode='a', header=False, index=False)
        chunk_count = chunk_count + 1
        total_size = total_size + field_df.index.size

if total_size == 0:
    print("Warning: Could not find any data for " + field_name + " = " + field_value)
print("Wrote %d records for %s = %s"%(total_size, field_name, field_value))
