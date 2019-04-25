import argparse
import os, ssl
import sys
import time
#import airr as airr
import pandas as pd
import numpy as np
import matplotlib
import matplotlib.pyplot as plt
from collections import OrderedDict

def performQueryAnalysis(input_file, query_xkey, query_ykey, query_xvalues, query_yvalues):
    # Create a numpy array (zeroed) of the correct size.
    data = np.zeros((len(query_yvalues), len(query_xvalues)))
    # Check to see if the file exists and return if not.
    if not os.path.isfile(input_file):
        print("ERROR: Could not open file ", input_file)
        return data 

    # Get the rearrangement data into a Pandas data frame.
    #airr_df = airr.load_rearrangement(input_file)
    chunk_size = 100000
    airr_df_reader =  pd.read_csv(input_file, sep='\t', chunksize=chunk_size)

    # Iterate over the block of data, gathering the stats for each X,Y
    # pair.
    count = 0
    for airr_df in airr_df_reader:
        # For each of the X query values
        xindex = 0
        for xvalue in query_xvalues:
            yindex = 0
            # For each of the Y query values
            for yvalue in query_yvalues:
                # Filter the data frame on values of interest.
                xfilter = airr_df[query_xkey].isin([xvalue])
                xfilter_data = airr_df.loc[xfilter]
                yfilter = xfilter_data[query_ykey].isin([yvalue])
                xyfilter_data = xfilter_data.loc[yfilter]
                #print(str(xyfilter_data[query_xkey]) + " " + str(xyfilter_data[query_ykey]))
                
                # Update the count for the sample we are considering
                data[yindex, xindex] = data[yindex, xindex] + len(xyfilter_data)
                yindex = yindex + 1
            xindex = xindex + 1
        count = count + len(airr_df)
        print("Done processing record " + str(count))

    print('\t', end='')
    for yvalue in query_yvalues:
        print(yvalue + '\t', end='')
    print('')

    # For each of the X query values
    xindex = 0
    for xvalue in query_xvalues:
        print(xvalue + '\t', end='')
        yindex = 0
        # For each of the Y query values
        for yvalue in query_yvalues:
            #print('   ' + query_xkey + '/' + str(xvalue) +
            #              ' , ' + query_ykey + '/' + str(yvalue) +
            #              ' ( ' + str(xindex) + ',' + str(yindex) + ' ) ' +
            #              ' = ' + str(data[yindex, xindex]))
            print(str(int(data[yindex, xindex])) + '\t', end='')
            yindex = yindex + 1
        print('')
        xindex = xindex + 1
    return data

def plotData(plot_data, xlabels, ylabels, title, filename):
    # Plot code borrowed from here: https://matplotlib.org/gallery/images_contours_and_fields/image_annotated_heatmap.html

    matplotlib.use('Agg')
    fig, ax = plt.subplots()
    im = ax.imshow(plot_data)
    # Create colorbar
    cbar = ax.figure.colorbar(im, orientation="horizontal", pad=0.2, aspect=40, ax=ax)
    cbar.ax.set_xlabel("Number of Annotations", va="top", fontsize=6)
    plt.setp(cbar.ax.get_yticklabels(), fontsize=6)
    plt.setp(cbar.ax.get_xticklabels(), fontsize=6)
    #cbar = ax.figure.colorbar(im, ax=ax)
    #cbar.ax.set_ylabel("Number of Annotations", rotation=-90, va="bottom")

    # We want to show all ticks...
    ax.set_xticks(np.arange(len(xlabels)))
    ax.set_yticks(np.arange(len(ylabels)))
    ax.grid(which="minor", color="w", linestyle='-', linewidth=3)
    ax.tick_params(which="minor", bottom=False, left=False)
    # ... and label them with the respective list entries
    ax.set_xticklabels(xlabels)
    ax.set_yticklabels(ylabels)
    ax.set_title(title)

    # Rotate the tick labels and set their alignment.
    #plt.setp(ax.get_xticklabels(), rotation=90, ha="right",
    #         fontsize=6, rotation_mode="anchor")
    plt.setp(ax.get_xticklabels(), rotation=90, ha="right",
            va="center", rotation_mode="anchor", fontsize=6)
    plt.setp(ax.get_yticklabels(), fontsize=6)

    fig.savefig(filename, transparent=False, dpi=240, bbox_inches="tight")

def getArguments():
    parser = argparse.ArgumentParser(
        formatter_class=argparse.RawDescriptionHelpFormatter,
        description="Note: for proper data processing, project --samples metadata should\n" +
        "generally be read first into the database before loading other data types."
    )

    parser.add_argument("api_xfield")
    parser.add_argument("api_yfield")
    parser.add_argument("graph_xvalues")
    parser.add_argument("graph_yvalues")
    parser.add_argument("input_file")
    parser.add_argument("output_file")
    parser.add_argument(
        "-v",
        "--verbose",
        action="store_true",
        help="Run the program in verbose mode. This option will generate a lot of output, but is recommended from a data provenance perspective as it will inform you of how it mapped input data columns into repository columns.")

    # Add configuration options
    #config_group = parser.add_argument_group("Configuration file options", "")
    #config_group.add_argument(
    #    "--mapfile",
    #    dest="mapfile",
    #    default="ireceptor.cfg",
    #    help="the iReceptor configuration file. Defaults to 'ireceptor.cfg' in the local directory where the command is run. This file contains the mappings between the AIRR Community field definitions, the annotation tool field definitions, and the fields and their names that are stored in the repository."
    #)

    options = parser.parse_args()
    return options


if __name__ == "__main__":
    # Get the command line arguments.
    options = getArguments()
    # Split the comma separated input string.
    xvalues = options.graph_xvalues.split(',')
    yvalues = options.graph_yvalues.split(',')
    # Perform the query analysis, gives us back a dictionary.
    data = performQueryAnalysis(options.input_file, options.api_xfield, options.api_yfield, xvalues, yvalues)
    # Graph the results
    title = options.api_xfield + " " + options.api_yfield + " Usage"
    plotData(data, xvalues, yvalues, title, options.output_file)

    # Return success
    sys.exit(0)

