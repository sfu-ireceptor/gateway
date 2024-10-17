# Imports
import argparse
import os, ssl
import sys
import time
import pandas as pd
import numpy as np
import matplotlib
matplotlib.use('Agg')
import matplotlib.pyplot as plt
from collections import OrderedDict

def performQueryAnalysis(input_file, field_name, num_values, sort_values=False):
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
    counts = airr_df[field_name].value_counts(sort=sort_values)

    if num_values > 0:
        counts = counts.head(num_values)

    return counts

def plotData(plot_data, title, filename):

    # Get the sixe of the data we are plotting
    plot_size = len(plot_data)
    print(plot_data)

    # Play with the font so it is a reasonable size
    font_size = 10
    dpi = 80
    rows_per_inch = dpi/(2*font_size)
    plot_height_inches = plot_size / rows_per_inch
    if plot_height_inches < 1.0:
        plot_height_inches = 1.0


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

    # Set the size of the figure.
    fig.set_figheight(int(plot_height_inches))
    
    # Calculate a reasonable output DPI - images can be
    # very large if there are a lot of gene calls. Max
    # resolution is 65536 (2^16)
    output_dpi = 240
    while int(plot_height_inches) * output_dpi > 65536:
        output_dpi = output_dpi/2

    # Write the file.
    fig.savefig(filename, transparent=False, dpi=output_dpi, bbox_inches="tight")
    return

def getArguments():
    parser = argparse.ArgumentParser(
        formatter_class=argparse.RawDescriptionHelpFormatter,
        description=""
    )

    # Field name for which we generate the histogram
    parser.add_argument("field_name")
    # Input file.
    parser.add_argument("input_file")
    # PNG and TSV output files
    parser.add_argument("png_output_file")
    parser.add_argument("tsv_output_file")
    # Count and sort parameters
    parser.add_argument("sort_values")
    parser.add_argument("num_values", type=int)
    # Title of the graph
    parser.add_argument("title")
    # Verbosity flag
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
    if options.sort_values.upper() == "TRUE":
        sort = True
    else:
        sort = False
    # Perform the query analysis, gives us back the data
    data = performQueryAnalysis(options.input_file, options.field_name,
                                options.num_values, sort)
    # Graph the results if we got some...
    title = options.title 
    if not data is None:
        # Plot the data and save the file
        plotData(data, title, options.png_output_file)
        # Save the data itself.
        data.to_csv(options.tsv_output_file, sep = '\t')
    else:
        sys.exit(2)
    # Return success
    print("Done writing graph to " + options.png_output_file)
    print("Done writing data to " + options.tsv_output_file)
    sys.exit(0)
