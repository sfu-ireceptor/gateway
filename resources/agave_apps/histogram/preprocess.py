import sys
import csv

with open(sys.argv[1], 'rb') as f:
	rows = csv.DictReader(f, dialect='excel-tab')
	for row in rows:
		if sys.argv[2] in row:
			print row[sys.argv[2]]
