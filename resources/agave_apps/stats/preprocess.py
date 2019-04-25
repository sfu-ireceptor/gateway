import sys
import pandas

filename = sys.argv[1]
field = sys.argv[2]
chunk_size = 100000

airr_df_reader = pandas.read_csv(filename, sep='\t', chunksize=chunk_size)
for airr_df in airr_df_reader:
    if field in airr_df:
        field_df = airr_df.loc[: ,field]
        for value in field_df:
            print(value)
