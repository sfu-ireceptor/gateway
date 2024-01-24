import sys
import argparse
import scanpy
import numpy

def getArguments():
    # Set up the command line parser
    parser = argparse.ArgumentParser(
        formatter_class=argparse.RawDescriptionHelpFormatter,
        description=""
    )

    # The directory where the matrix.mtx, features.tsv, and barcodes.tsv files
    # reside. These files must be compressed.
    parser.add_argument("mtx_directory", help="The 10X MTX input directory. This should contain the matrix.mtx.gz, features.tsv.gz, and barcodes.tsv.gz (compressed).")

    # The output file (with or without path) for the h5ad file.
    parser.add_argument("output_file", help="The h5ad output file")

    # Request normaliztion of the counts for each cell to the given value.
    parser.add_argument(
        "--normalize",
        action="store_true",
        help="Request each cell count to be normalized")

    # Request normaliztion of the counts for each cell to the given value.
    parser.add_argument(
        "--normalize_value",
        dest="normalize_value",
        type=float,
        default=10000.0,
        help="Request each cell count to be normalized to this value")

    # Perform logarithmic processing - as required by some tools (celltypist)
    parser.add_argument(
        "--log1p",
        dest="log1p",
        action="store_true",
        help="Request the data to be transformed to a logarithmic representation.")

    # Handle verbose processing
    parser.add_argument(
        "-v",
        "--verbose",
        action="store_true",
        help="Run the program in verbose mode. This option will generate debug output.")


    # Parse the command line arguements.
    options = parser.parse_args()
    return options



if __name__ == "__main__":
    # Get the command line arguments.
    options = getArguments()

    # Read in the 10X directory
    print("IR-INFO: Reading 10X matrix directory " + options.mtx_directory)
    adata = scanpy.read_10x_mtx(options.mtx_directory)

    # If normalization requested, normalize. Default normalize to 1.
    if options.normalize:
        print("IR-INFO: Normalizing cell counts to %f"%(options.normalize_value))
        scanpy.pp.normalize_total(adata, target_sum=options.normalize_value)
    # If log scaling requested, do it.
    if options.log1p:
        print("IR-INFO: Performing log scaling" )
        scanpy.pp.log1p(adata)
        # scanpy has a bug, doesn't save "None" data into files.
        # log1p sets base = None, so we want to change it toe numpy.e
        adata.uns["log1p"]["base"]=numpy.e

    # Write the h5ad file
    print("IR-INFO: Writing output to " + options.output_file)
    print(adata.uns)
    adata.write(options.output_file)

    # Done
    sys.exit(0)

