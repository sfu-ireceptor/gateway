#!/usr/bin/python
# from http://www.codejuggle.dj/how-to-convert-between-csv-and-tsv/

import csv
import sys
if len(sys.argv) < 3:
    sys.exit("Usage: csv_to_tsv.py <input.csv> <output.tsv>")

csv.field_size_limit(sys.maxsize)
csv.writer(file(sys.argv[2], 'w+'), delimiter="\t").writerows(csv.reader(open(sys.argv[1])))
