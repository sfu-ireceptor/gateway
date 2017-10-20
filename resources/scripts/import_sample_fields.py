import csv

print('airr-full', 'airr', 'ir-v1', 'ir-v2', 'ir-full', 'ir-short')
with open('mapping.csv') as f:
    reader = csv.reader(f, delimiter=',')
    for r in reader:
        # print(r)
        print(r[1], r[2], r[6], r[14], r[28], r[29])
        # break
