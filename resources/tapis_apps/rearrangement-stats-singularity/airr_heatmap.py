# Import used...
import argparse
import os, ssl
import sys
import time
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

    # Get the rearrangement data into a Pandas data frame. We force everything to be
    # a string so we can do our index/slicing without worrying about type.
    chunk_size = 100000
    airr_df_reader =  pd.read_csv(input_file, sep='\t',
            dtype={query_xkey:str,query_ykey:str},
            chunksize=chunk_size)

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
                
                # Update the count for the sample we are considering
                data[yindex, xindex] = data[yindex, xindex] + len(xyfilter_data)
                yindex = yindex + 1
            xindex = xindex + 1
        count = count + len(airr_df)
        print("Done processing record " + str(count))

    # Print out the data we got for the record.
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
            print(str(int(data[yindex, xindex])) + '\t', end='')
            yindex = yindex + 1
        print('')
        xindex = xindex + 1
    return data

def plotData(plot_data, xlabels, ylabels, title, filename):
    # Plot code borrowed from here: 
    # https://matplotlib.org/gallery/images_contours_and_fields/image_annotated_heatmap.html
    matplotlib.use('Agg')
    fig, ax = plt.subplots()
    im = ax.imshow(plot_data)
    # Establish figure fontsize
    fontsize = 10

    # Create colorbar
    #cbar = ax.figure.colorbar(im, orientation="horizontal", pad=0.2, aspect=40, ax=ax)
    cbar = ax.figure.colorbar(im, location="right")
    cbar.ax.set_xlabel("Number of Annotations", va="top", fontsize=fontsize)
    plt.setp(cbar.ax.get_yticklabels(), fontsize=fontsize)
    plt.setp(cbar.ax.get_xticklabels(), fontsize=fontsize)

    # We want to show all ticks...
    xrows = len(xlabels)
    yrows = len(ylabels)
    ax.set_xticks(np.arange(xrows))
    ax.set_yticks(np.arange(yrows))
    ax.grid(which="minor", color="w", linestyle='-', linewidth=3)
    ax.tick_params(which="minor", bottom=False, left=False)
    # ... and label them with the respective list entries
    ax.set_xticklabels(xlabels)
    ax.set_yticklabels(ylabels)
    ax.set_title(title)

    # Rotate the tick labels and set their alignment.
    plt.setp(ax.get_xticklabels(), rotation=90, ha="right",
            va="center", rotation_mode="anchor", fontsize=fontsize)
    plt.setp(ax.get_yticklabels(), fontsize=fontsize)

    # Determine size of figure
    dpi = matplotlib.rcParams["figure.dpi"]
    print('dpi = ' + str(dpi))
    rows_per_inch = dpi/(2*fontsize)
    x_plot_height_inches = xrows / rows_per_inch
    y_plot_height_inches = yrows / rows_per_inch
    print('x_plot_height_inches = ' + str(x_plot_height_inches))
    print('y_plot_height_inches = ' + str(y_plot_height_inches))
    #fig.set_figwidth(int(x_plot_height_inches)+1)
    #fig.set_figheight(int(y_plot_height_inches)+1)
    im.figure.set_figwidth(int(x_plot_height_inches)+1)
    im.figure.set_figheight(int(y_plot_height_inches)+1)

    # Write the file.
    fig.savefig(filename, transparent=False, dpi=240, bbox_inches="tight")

def getArguments():
    parser = argparse.ArgumentParser(
        formatter_class=argparse.RawDescriptionHelpFormatter,
        description=""
    )

    # API x and y fields to use
    parser.add_argument("api_xfield")
    parser.add_argument("api_yfield")
    # Values to graph for the x and y axes
    parser.add_argument("graph_xvalues")
    parser.add_argument("graph_yvalues")
    # The input file
    parser.add_argument("input_file")
    # PNG and TSV output file
    parser.add_argument("png_output_file")
    parser.add_argument("tsv_output_file")
    # Title for the graph
    parser.add_argument("title")
    # Verbosity arguement
    parser.add_argument(
        "-v",
        "--verbose",
        action="store_true",
        help="Run the program in verbose mode. This option will generate a lot of output, but is useful to understand the processing being carried out.")

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
    title = options.title
    if not data is None:
        # Plot the data and save the file
        plotData(data, xvalues, yvalues, title, options.png_output_file)
        # Save the data itself
        df = pd.DataFrame(data=data, index=yvalues, columns=xvalues)
        df.to_csv(options.tsv_output_file, sep='\t')
    else:
        sys.exit(2)


    # Return success
    sys.exit(0)

