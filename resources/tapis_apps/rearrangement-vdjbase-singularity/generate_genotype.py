import argparse
import sys
import json
import pandas as pd

ap = argparse.ArgumentParser()

ap.add_argument("--genotype_file", required=True,
   help="path to genotype.tsv output file from VDJBase")
ap.add_argument("--repertoire_id", required=True,
   help="repertoire id in repository")
ap.add_argument("--data_processing_id", required=True,
   help="data processing id in repository")
ap.add_argument("--sample_processing_id", required=True,
   help="sample_processing_id in repository")
ap.add_argument("--data_processing_file", required=True,
   help="data processing file name in repository")
ap.add_argument("--receptor_genotype_set_id", required=True,
   help="value to use for receptor_genotype_set_id; typically we use subject_id e.g. 14711")
ap.add_argument("--receptor_genotype_id", required=True,
   help="value to use for receptor_genotype_id; e.g. 14711-IGH")
ap.add_argument("--germline_database", default="",
        help="value to use for germline_set_ref; e.g. IMGT:201736-4")
ap.add_argument("--out_name", required=True,
   help="name of output file as in out_name.json; typically use sample_id; e.g. 14711_CSF")

args = vars(ap.parse_args())
print("germline = %s"%(args['germline_database']))
print("data processing file = %s"%(args['data_processing_file']))

# create the dataframe
try:
    genotype_df = pd.read_csv(args["genotype_file"], sep="\t")
except Exception as e:
    print('ERROR: Unable to read TSV file %s'%(args["genotype_file"]))
    print('ERROR: Reason =' + str(e))
    sys.exit(1)


# Check to see if columns of interest exist.
if not 'gene' in genotype_df or not 'GENOTYPED_ALLELES' in genotype_df:
    print('ERROR: Could not find required fields (%s, %s) in file %s'%
            ('gene', 'GENOTYPED_ALLELES', args["genotype_file"]))
    sys.exit(1)

# select the columns of interest
genotype_df = genotype_df[['gene','GENOTYPED_ALLELES']]

documented_allele_list = list()
undocumented_allele_list = list()
deleted_genes_list = list()

# iterate through genotype df
for index, row in genotype_df.iterrows():

    # split the comma-sep genotyped alleles into a list of alleles.
    # iterate through the alleles in the list and construct the full allele name.
    if row["GENOTYPED_ALLELES"] != "Deletion":
        allele_numbers = row["GENOTYPED_ALLELES"].split(",")
        for allele_number in allele_numbers:
            if "_" not in allele_number:
                allele_name = row["gene"] + "*" + allele_number

                # Create a documented_alleles - which is a list of dictionaries
                documented_allele_dict = dict()
                documented_allele_dict["label"] = allele_name
                documented_allele_dict["germline_set_ref"] = args["germline_database"]
                documented_allele_list.append(documented_allele_dict)

            else:
                undocumented_allele = allele_number
                undocumented_allele_dict = dict()
                undocumented_allele_dict["allele_name"] = undocumented_allele
                undocumented_allele_dict["sequence"] = "sequence"
                undocumented_allele_list.append(undocumented_allele_dict)

    if row["GENOTYPED_ALLELES"] == "Deletion":
        deleted_gene_name = row["gene"]
        deleted_genes_dict = dict()
        deleted_genes_dict["label"] = deleted_gene_name
        deleted_genes_dict["germline_set_ref"] = args["germline_database"]
        deleted_genes_list.append(deleted_genes_dict)

# Create a genotype class object
genotype_class_dict = dict()
genotype_class_dict["receptor_genotype_id"] = args["receptor_genotype_id"]
genotype_class_dict["locus"] = "IGH"
genotype_class_dict["inference_process"] = "repertoire_sequencing"
genotype_class_dict["documented_alleles"] = documented_allele_list
genotype_class_dict["undocumented_alleles"] = undocumented_allele_list
genotype_class_dict["deleted_genes"] = deleted_genes_list

# Create a genotype class list that is an array, with one element.
genotype_class_list = list()
genotype_class_list.append(genotype_class_dict)

# Create a genotype set object
genotype_set_dict = dict()
genotype_set_dict["receptor_genotype_set_id"] = args["receptor_genotype_set_id"]
genotype_set_dict["genotype_class_list"] = genotype_class_list

# Create a genotype object
genotype_dict = dict()
genotype_dict["receptor_genotype_set"] = genotype_set_dict

# Create a subject object
subject_dict = dict()
subject_dict["genotype"] = genotype_dict


# Create a data processing object
data_proc_list = list()
data_proc_dict = dict()
# Need to handle lists as they come in from the command line properly
# as a JSON list. Also handle a single string as a special case.
if "[" in args["data_processing_file"]:
    print("List - %s "%(args["data_processing_file"]))
    # JSON loads only likes double quote strings so need
    # to replace single quote string where necessary.
    list_string = args["data_processing_file"]
    list_string = list_string.replace('\'','\"')
    data_files_list = json.loads(list_string)
else:
    print("String - %s"%(args["data_processing_file"]))
    data_files_list = list()
    data_files_list.append(args["data_processing_file"])
data_proc_dict["data_processing_files"] = data_files_list
if "data_processing_id" in args:
    data_proc_dict["data_processing_id"] = args["data_processing_id"]
data_proc_list.append(data_proc_dict)

# Crete a sample object
sample_dict = dict()
if "sample_processing_id" in args:
    sample_dict["sample_processing_id"] = args["sample_processing_id"]

# Create a repertoire object
repertoire_dict = dict()
repertoire_dict["repertoire_id"] = str(args["repertoire_id"])
repertoire_dict["subject"] = subject_dict
repertoire_dict["sample"] = sample_dict
repertoire_dict["data_processing"] = data_proc_list

repertoire_list = list()
repertoire_list.append(repertoire_dict)

# Create the top object
base_object = dict()
base_object["Repertoire"] = repertoire_list

with open(args["out_name"], "w") as outfile:
    json.dump(base_object, outfile, indent=4)
