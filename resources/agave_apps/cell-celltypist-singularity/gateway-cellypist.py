import sys
import argparse
import scanpy
import numpy
import celltypist
from celltypist import models

def getArguments():
    # Set up the command line parser
    parser = argparse.ArgumentParser(
        formatter_class=argparse.RawDescriptionHelpFormatter,
        description=""
    )

    # The directory where the matrix.mtx, features.tsv, and barcodes.tsv files
    # reside. These files must be compressed.
    parser.add_argument("input_file", help="The input Anndata file. This is expected to be normalized to a total of 10000 counts per cell and logarithmically scaled")

    # The output file (with or without path) for the h5ad file.
    parser.add_argument("output_file", help="The Anndata output file")

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

    # Output some cell typist info about the models
    print("IR-INFO: CellTypist data models:")
    print(models.models_description())

    # Load in a default model
    model = models.Model.load(model = 'Immune_All_Low.pkl')
    print("IR-INFO: Default model info:")
    print(model)
    print(model.cell_types)

    # Read in the h5ad file
    print("IR-INFO: Reading 10X matrix directory " + options.input_file)
    adata = scanpy.read(options.input_file)

    # We turn on the majority-voting classifier (majority_voting = True), which refines
    # cell identities within local subclusters after an over-clustering approach at the
    # cost of increased runtime.
    #
    # The results include both predicted cell type labels (predicted_labels),
    # over-clustering result (over_clustering), and predicted labels after majority voting
    # in local subclusters (majority_voting). Note in the predicted_labels, each query cell
    # gets its inferred label by choosing the most probable cell type among all possible
    # cell types in the given model.
    predictions = celltypist.annotate(adata, model = 'Immune_All_Low.pkl', majority_voting = True)

    # Get an `AnnData` with predicted labels embedded into the cell metadata columns.
    prediction_adata = predictions.to_adata()
    print("IR-INFO: Prediction observations:")
    print(prediction_adata.obs)

    # Write the h5ad file
    print("IR-INFO: Writing output to " + options.output_file)
    print(adata.uns)
    prediction_adata.write(options.output_file)

    # Done
    sys.exit(0)

