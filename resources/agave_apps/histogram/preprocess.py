import sys
import csv

with open(sys.argv[1], 'rb') as f:
	rows = csv.DictReader(f, dialect='excel-tab')
	for row in rows:
		print row['junction_nt_length']
