import sys
import argparse
import json
import time
import pandas as pd
import numpy as np

# Convert AIRR Cell/GEX to 10X Cell/Gex
def generate10X(airr_cell_file, airr_gex_file, 
                feature_file, barcode_file, matrix_file, verbose):
    convert_t_start = time.perf_counter()
    # Open the AIRR Cell JSON file.
    try:
        with open(airr_cell_file) as f:
            cell_dict = json.load(f)
    except Exception as e:
        print('ERROR: Unable to read AIRR Cell JSON file %s'%(airr_cell_file))
        print('ERROR: Reason =' + str(e))
        return False
    if verbose:
        print(cell_dict)

    # Open the AIRR GEX JSON file.
    try:
        with open(airr_gex_file) as f:
            gex_dict = json.load(f)
    except Exception as e:
        print('ERROR: Unable to read AIRR GEX JSON file %s'%(airr_gex_file))
        print('ERROR: Reason =' + str(e))
        return False
    if verbose:
        print(gex_dict)

    # Check for valid Cell data and write out the barcodes file. The AIRR file
    # should have a JSON Cell object that is an array of Cells.
    cell_names = []
    if 'Cell' in cell_dict:
        cell_array = cell_dict['Cell']
        if isinstance(cell_array, list):
            num_cells = len(cell_array)
            print('Num cells = %s'%(num_cells),flush=True)
            # If file structure is good, output and cache the cell_ids
            try:
                with open(barcode_file, 'w') as file_object:
                    for cell in cell_array:
                        if not cell['cell_id'] in cell_names:
                            cell_names.append(cell['cell_id'])
                            cell_info = cell['cell_id'] + '\n'
                            file_object.write(cell_info)
                        else:
                            cell_names.append(cell['cell_id'] + '-dup')
                            cell_info = cell['cell_id'] + '-dup' + '\n'
                            file_object.write(cell_info)

            except Exception as e:
                print('ERROR: Unable to write 10X barcode file  %s'%(barcode_file))
                print('ERROR: Reason = ' + str(e))
                return False
        else:
            print("ERROR: Cell object is not an array in file %s"%(airr_cell_file))
            return False
    else:
        print("ERROR: Could not find Cell object in file %s"%(airr_cell_file))
        return False

    # Check for valid CellExpression data and write out the features and matrix files.
    # The AIRR file should have a JSON CellExpression object that is an array of cell
    # properties..
    if 'CellExpression' in gex_dict:
        gex_array = gex_dict['CellExpression']
        if isinstance(gex_array, list):
            num_gex = len(gex_array)
            print('Num gex = %s'%(num_gex), flush=True)
        else:
            print("ERROR: CellExpression object is not an array in file %s"%(airr_gex_file))
            return False
    else:
        print("ERROR: Could not find CellExpression object in file %s"%(airr_gex_file))
        return False

    print('starting to generate matrix', flush=True)
    property_names = []
    property_count = 0
    try:
            # Open the matrix and featire files. We write them in one pass.
            matrix_fh = open(matrix_file+'.tmp', 'w')
            feature_fh = open(feature_file, 'w')
            # Write the matrix header as per 10X files. We don't add the count
            # line yet as we want to we don't have the number of features.
            matrix_fh.write('%%MatrixMarket matrix coordinate integer general\n')
            matrix_fh.write('%metadata_json: {"software_version": "iReceptor Gateway v4.0", "format_version": 2}\n')
            # Set up some counting and caching stuff
            count = 0
            last_cell_id = ''
            last_cell_index = -1
            property_count = 0
            t_start = time.perf_counter()
            # Iterate over each cell property.
            for cell_property in gex_array:
                # If we don't have it listed as a property yet, add it.
                if not cell_property['property']['id'] in property_names:
                    # Add it to our list
                    property_names.append(cell_property['property']['id'])
                    # The AIRR standard uses CURIEs for propertys. Handle the case
                    # where an ID is a CURIE of the form ENSG:ENSGXXXXX. The 10X names
                    # don't have the CURIE part.
                    property_str = cell_property['property']['id']
                    if property_str.find(':') >= 0:
                        property_str = property_str.split(':')[1]
                    # Prepare the property for writing, and write it to the feature file.
                    feature = property_str + '\t' + cell_property['property']['label'] + '\tGene Expression\n'
                    feature_fh.write(feature)
                    property_count = property_count + 1
                    # Store the index of the property (the one we just added).
                    property_index = property_count
                else:
                    # If we already have the property look it up to get the index.
                    try:
                        property_index = property_names.index(cell_property['property']['id'])+1
                    except Exception as e:
                        print('ERROR: Unable to find property %s in GEX file %s - skipping...'%(airr_gex_file))
                        print('ERROR: Reason = ' + str(e))
                        continue


                # Get the cell index. Since all propertyies for a cell are often loaded
                # together we can use a cache so we don't have to look it up every time.
                if last_cell_id == cell_property['cell_id']:
                    cell_index = last_cell_index
                else:
                    cell_index = cell_names.index(cell_property['cell_id']) + 1

                # Pring out some progress monitoring.
                if count % 10000 == 0:
                    t_end = time.perf_counter()
                    print('count = %s (%d s, %f percent done)'%(count, t_end-t_start, (count/num_gex)*100), flush=True)
                    t_start = time.perf_counter()

                # Prepare the matrix string for writing and write it.
                matrix_info = str(property_index) + ' ' + str(cell_index) + ' ' + str(cell_property['value']) + '\n'
                matrix_fh.write(matrix_info)
                count = count + 1
            print('Property count = %d'%(property_count), flush=True)
    except Exception as e:
        print('ERROR: Unable to read AIRR GEX JSON file %s'%(airr_gex_file))
        print('ERROR: Reason = ' + str(e))
        return False

    # Close files.
    matrix_fh.close()
    feature_fh.close()

    # Not sure this is the best way to do this, but...
    # We only now have the number of features, so we rewrite the
    # file with a new third line with the counts of the three files.
    with open(matrix_file+'.tmp','r') as f:
      with open(matrix_file,'w') as f2:
        line_count = 1
        for line in f:
            # If we are on line 2, add the third line with the counts.
            if line_count == 2:
                f2.write(line)
                f2.write(str(property_count) + ' ' + str(num_cells) + ' ' + str(num_gex) + '\n')
            else:
                f2.write(line)
            line_count = line_count + 1

    convert_t_end = time.perf_counter()
    print('Conversion time = %d s)'%(convert_t_end-convert_t_start), flush=True)
    return True

def getArguments():
    # Set up the command line parser
    parser = argparse.ArgumentParser(
        formatter_class=argparse.RawDescriptionHelpFormatter,
        description=""
    )
    parser = argparse.ArgumentParser()

    # The t-cell/b-cell barcode file name
    parser.add_argument("airr_cell_file")
    # The cell/barcode file name
    parser.add_argument("airr_gex_file")
    # The repertoire_id to summarize
    parser.add_argument("feature_file")
    # The repertoire_id to summarize
    parser.add_argument("barcode_file")
    # The repertoire_id to summarize
    parser.add_argument("matrix_file")
    # Verbosity flag
    parser.add_argument(
        "-v",
        "--verbose",
        action="store_true",
        help="Run the program in verbose mode.")

    # Parse the command line arguements.
    options = parser.parse_args()
    return options


if __name__ == "__main__":
    # Get the command line arguments.
    options = getArguments()

    # Process and produce a AIRR GEX file from the 10X VDJ pipeline. This uses the
    # standard 10X cell_barcodes.json to determine which cells are b or t-cells but still
    # uses the count pipeline barcodes.tsv file for the cell indexes and features.tsv for
    # the gene features. It uses the 10X matrix.mtx file (stripped of headers) to get the
    # count for each cell/gene pair. It has three columns, the first column is an index
    # into the features.tsv, the second column is the index into the barcodes.tsv file,
    # and the third column is the count of the number of times that feature was found
    # for that cell.
    success = generate10X(options.airr_cell_file, options.airr_gex_file,
                           options.feature_file, options.barcode_file, options.matrix_file,
                           options.verbose)

    # Return success if successful
    if not success:
        print('ERROR: Unable to process AIRR files (cells=%s, gex=%s)'%
              (options.airr_cell_file, options.airr_gex_file))
        sys.exit(1)
