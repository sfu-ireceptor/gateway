import sys
import csv

with open(sys.argv[1], 'rb') as f:
	rows = csv.DictReader(f, dialect='excel-tab')
	for row in rows:
		if 'junction_nt_length' in row:
			print row['junction_nt_length']
		elif 'junction_length' in row:
			print row['junction_length']