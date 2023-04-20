import string
import sys
import argparse
import json
import time
import numpy
import scanpy

def getArguments():
    # Set up the command line parser
    parser = argparse.ArgumentParser(
        formatter_class=argparse.RawDescriptionHelpFormatter,
        description=""
    )
    parser = argparse.ArgumentParser()

    # The h5ad file to process
    parser.add_argument("input_file")
    # The h5ad file to write
    parser.add_argument("output_file")

    # Request normaliztion of the counts for each cell to the given value.
    parser.add_argument(
        "--normalize_value",
        dest="normalize_value",
        type=float,
        default=10000.0,
        help="Request each cell count to be normalized to this value")

    # Parse the command line arguements.
    options = parser.parse_args()
    return options

if __name__ == "__main__":
    total_start = time.perf_counter()
    # Get the command line arguments.
    options = getArguments()

    print("IR-INFO: Reading h5ad file " + options.input_file, flush=True)
    adata = scanpy.read(options.input_file)

    # Return success if successful
    if adata is None: 
        print('ERROR: Unable to process h5adfile %s'%(options.input_file))
        sys.exit(1)

    # If normalization requested, normalize. Default normalize to 1.
    print("IR-INFO: Normalizing cell counts to %f"%(options.normalize_value))
    scanpy.pp.normalize_total(adata, target_sum=options.normalize_value)

    # If log scaling requested, do it.
    print("IR-INFO: Performing log scaling" )
    scanpy.pp.log1p(adata)
    # scanpy has a bug, doesn't save "None" data into files.
    # log1p sets base = None, so we want to change it toe numpy.e
    adata.uns["log1p"]["base"]=numpy.e

    # Write the output to the output file.
    print("IR-INFO: Writing file %s"%(options.output_file))
    adata.write(options.output_file)
    print("IR-INFO: Done writing file %s"%(options.output_file))
    print(adata.to_df())
    total_end = time.perf_counter()
    print('IR-INFO: Total time = %d s'%(total_end-total_start))

    sys.exit(0)
