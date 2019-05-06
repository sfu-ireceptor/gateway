import argparse
import os, ssl
import sys
import time
#import airr as airr
import pandas as pd
import numpy as np
import matplotlib
matplotlib.use('Agg')
import matplotlib.pyplot as plt
from collections import OrderedDict

def performQueryAnalysis(input_file, field_name):
    # Check to see if the file exists and return if not.
    if not os.path.isfile(input_file):
        print("ERROR: Could not open file ", input_file)
        return None 

    # Get the rearrangement data into a Pandas data frame.
    airr_df = pd.read_csv(input_file, sep='\t')

    # Check that we have the field of interest
    if not field_name in airr_df.columns:
        print("ERROR: Could not find " + field_name + " in file ", input_file)
        return None

    # Count up the number for each column value.
    counts = airr_df[field_name].value_counts(sort=False)
    counts = counts.sort_index()
    return counts

def plotData(plot_data, title, filename):

    # Get the sixe of the data we are plotting
    plot_size = len(plot_data)
    print(plot_data)

    # Play with the font so it is a reasonable size
    font_size = 14
    font_reduction = int(plot_size/4)
    font_size = font_size - font_reduction
    if font_size < 6: font_size = 6

    # Create the graph...
    fig, ax = plt.subplots()
    ax.set_title(title)
    ax.set_xlabel("Frequency", va="top", fontsize=font_size)

    # Make it a bar graph using the names and the data provided
    labels = plot_data.index
    ax.barh(range(0,plot_size), plot_data, tick_label=labels)
    ax.tick_params(which="major", top=False, right=False)

    # Set up the axis labels...
    plt.setp(ax.get_xticklabels(), fontsize=font_size)
    plt.setp(ax.get_yticklabels(), va="center", fontsize=font_size)
    if plot_size > 30:
        ax.set_yticklabels(labels, minor=True)

    # Write the file.
    fig.savefig(filename, transparent=False, dpi=240, bbox_inches="tight")
    return

def getArguments():
    parser = argparse.ArgumentParser(
        formatter_class=argparse.RawDescriptionHelpFormatter,
        description=""
    )

    parser.add_argument("field_name")
    parser.add_argument("input_file")
    parser.add_argument("output_file")
    parser.add_argument(
        "-v",
        "--verbose",
        action="store_true",
        help="Run the program in verbose mode.")

    options = parser.parse_args()
    return options

if __name__ == "__main__":
    # Get the command line arguments.
    options = getArguments()
    # Perform the query analysis, gives us back the data
    data = performQueryAnalysis(options.input_file, options.field_name)
    # Graph the results if we got some...
    title = options.field_name 
    if not data is None:
        plotData(data, title, options.output_file)
    else:
        sys.exit(2)
    # Return success
    print("Done writing graph to " + options.output_file)
    sys.exit(0)
