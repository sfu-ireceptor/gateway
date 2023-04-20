import sys
import csv

with open(sys.argv[1], 'rb') as csvfile:
	rows = csv.reader(csvfile)
	for row in rows:
		print row[25]

