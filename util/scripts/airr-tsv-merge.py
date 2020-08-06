#!/usr/bin/python3

# parse arguments
import argparse
parser = argparse.ArgumentParser()
parser.add_argument('-i','--input', nargs='+', help='<Required> Files to merge', required=True)
parser.add_argument('-o','--output', help='<Required> Output file', required=True)
args = parser.parse_args()

# merge input files into output file
import airr
airr.merge_rearrangement(args.output, args.input, drop=False, debug=False)
