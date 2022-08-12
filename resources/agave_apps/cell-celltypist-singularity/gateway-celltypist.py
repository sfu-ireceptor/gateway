import sys
import os
import argparse

from matplotlib import pyplot
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

    # The filename (with or without full path) for the input file.
    parser.add_argument("input_file", help="The input Anndata file. This is expected to be normalized to a total of 10000 counts per cell and logarithmically scaled")

    # The output directory (full or relative path) for output
    parser.add_argument("output_directory", help="The directory where the plotting and report files are saved.")

    # The output file (filename only) for the h5ad file.
    parser.add_argument("output_file", help="The Anndata output filename, will be written to output_directory")

    # The output file (filename only) for the h5ad file.
    parser.add_argument("title", help="The title to use for the figures.")

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
    print(models.models_description(), flush=True)

    # Load in a default model
    model = models.Model.load(model = 'Immune_All_Low.pkl')
    print("IR-INFO: Default model info:")
    print(model)
    print(model.cell_types, flush=True)

    # Read in the h5ad file
    print("IR-INFO: Reading Cell file " + options.input_file, flush=True)
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
    print(prediction_adata.obs, flush=True)
    print(prediction_adata.to_df(), flush=True)


    # Export figures with labels external (celltypist plots are messy)
    scanpy.tl.umap(prediction_adata)
    scanpy.pl.umap(prediction_adata, color = ['predicted_labels'], title = options.title + ' (majority)')
    pyplot.tight_layout()
    pyplot.savefig(os.path.join(options.output_directory, 'predicted_labels_v2' + '.pdf'))
    scanpy.pl.umap(prediction_adata, color = ['majority_voting'], title = 'CellTypist (majority vote)')
    pyplot.tight_layout()
    pyplot.savefig(os.path.join(options.output_directory, 'majority_voting_v2' + '.pdf'))

    # Write output
    predictions.to_table(folder = options.output_directory, prefix="", xlsx = True)

    # Plot results
    predictions.to_plots(folder = options.output_directory, plot_probability = True)

    # Write the h5ad file
    print("IR-INFO: Writing output to " + options.output_directory + "/" + options.output_file, flush=True)
    prediction_adata.write(options.output_directory + "/" + options.output_file)

    # Done
    sys.exit(0)

