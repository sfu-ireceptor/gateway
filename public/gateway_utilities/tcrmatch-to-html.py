# Imports
import sys
import pandas
import argparse

def getArguments():
    parser = argparse.ArgumentParser(
        formatter_class=argparse.RawDescriptionHelpFormatter,
        description=""
    )

    # API x and y fields to use
    parser.add_argument("filename")

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
    url_str = '<a href="https://www.iedb.org/receptor/%s">%s</a>' 
    df['receptor_group'] = df['receptor_group'].apply(lambda rid: url_str%(rid,rid))
    # If we have long string, limit its length and provide hover over full text
    df['epitope'] = df['epitope'].apply(str_limit_hover)
    df['antigen'] = df['antigen'].apply(str_limit_hover)
    df['organism'] = df['organism'].apply(str_limit_hover)

    # Output the dataframe as an HTML table.
    html_string = df.to_html(escape=False, classes=['table', 'table-striped'])
    print(html_string)
