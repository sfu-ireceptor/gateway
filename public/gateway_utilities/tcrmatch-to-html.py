# Imports
import sys
import pandas
import argparse

def getArguments():
    parser = argparse.ArgumentParser(
        formatter_class=argparse.RawDescriptionHelpFormatter,
        description=""
    )

    # Filename to load
    parser.add_argument("filename")
    # Max width of columns
    parser.add_argument(
        "-m",
        "--max_width",
        type=int,
        default=20,
        help="Maximum width of html columns.")

    options = parser.parse_args()
    return options

# Function to generate HTML div code that provides hover over
# of long text with the main text shortened for readability.
def str_limit_hover(base_str, max_length=20):
    if len(str(base_str)) > max_length:
       epitope_str = '<div title="%s">%s...</div>'%(base_str, base_str[0:max_length]) 
    else:
       epitope_str = '<div>%s</div>'%(base_str)
    return epitope_str
        

# A simple program that takes a TSV file and generates an HTML table
if __name__ == "__main__":

    # Get the arguments
    options = getArguments()

    # Read in the TSV file
    try:
        df = pandas.read_csv(options.filename, sep='\t', low_memory=False)
    except Exception as e:
        print('ERROR: Unable to read TSV file %s'%(options.filename))
        print('ERROR: Reason =' + str(e))
        sys.exit(1)

    # Convert the receptor_group column to be a URL
    if 'receptor_group' in df.columns:
        url_str = '<a href="https://www.iedb.org/receptor/%s">%s</a>' 
        df['receptor_group'] = df['receptor_group'].apply(lambda rid: url_str%(rid,rid))
    # If we have long string, limit its length and provide hover over full text
    if 'epitope' in df.columns:
        df['epitope'] = df['epitope'].apply((lambda x: str_limit_hover(x, options.max_width)))
    if 'antigen' in df.columns:
        df['antigen'] = df['antigen'].apply((lambda x: str_limit_hover(x, options.max_width)))
    if 'organism' in df.columns:
        df['organism'] = df['organism'].apply((lambda x: str_limit_hover(x, options.max_width)))

    # Output the dataframe as an HTML table.
    html_string = df.to_html(index=False, escape=False, classes=['table', 'table-striped'])
    print(html_string)
