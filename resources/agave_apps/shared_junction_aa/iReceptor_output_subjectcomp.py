#!/usr/bin/env python

import os
import argparse

import collections

import plotly.plotly as py
import plotly.offline as offly
import plotly.graph_objs as go
import plotly.figure_factory as ff

import pandas as pd

##-- Read the input parameters


parser = argparse.ArgumentParser(description='An application to parse the iReceptor gateway output sequences and compare between samples or subjects')


#- Required positional argument
parser.add_argument('-seqs_dir', type = str,
                    help='Required directory name with location that contains the list of files')
parser.add_argument('-output_dir', type = str,
                    help='Required directory name with location for the output files')

#- Optional positional argument
parser.add_argument('-pairwise_limit', type = int, nargs = '?', const = 10,
                    help='An optional limit for the number of samples for pair wise comparison')
parser.add_argument('-outfile_tag', type=str, nargs = '?', const = 'comparison_results',
                    help='An optional outputfile tag for the output files')

args = parser.parse_args()

print("\nThe arguments are: ",args.seqs_dir, "\t", args.outfile_tag)

if args.outfile_tag is None:
    args.outfile_tag = 'results'

if args.pairwise_limit is None:
    args.pairwise_limit = 10

##-- Get the parameters

functional_filter = True

##-- Open output files

pwstats_junctions_filenm = args.output_dir + "/" + 'pairwise_counts_shared_junctions.txt'
pwstats_junctions_file = open(pwstats_junctions_filenm,'w')
# pwstats_junctions_file = open()


##-- Get the controlled vocabulary

# repositories = ['']

# subject_id = 'subject_id'
# sample_id = 'sample_id'


# subject_id = 'ir_project_sample_id'
# sample_id = 'rearrangement_set_id'

##-- Get the list of files from the input directory location

list_of_files = os.listdir(args.seqs_dir)
os.chdir(args.seqs_dir)

##-- Get the dictionary of repositories and the repository files from the sequence data files available for each of the repositories

repositories = collections.OrderedDict()

for curr_file in list_of_files:
    
    if curr_file == "info.txt":
        continue

    currfilenm_wext = os.path.basename(curr_file)
    currfilenm_woext = os.path.splitext(currfilenm_wext)[0]

    repositories[currfilenm_woext] = currfilenm_wext


print("\nRepository files are: ",repositories)
##-- Get the dictionary of junction sequences data for each repository per sample

persample_junctions = collections.OrderedDict()
uniq_sample_metadata = collections.defaultdict(collections.OrderedDict)

persample_junctions['nucleotides'] = collections.OrderedDict()
persample_junctions['aminos'] = collections.OrderedDict()


for each_repository in repositories:

    curr_results_file = repositories[each_repository]    
    curr_rep_df = pd.read_table(curr_results_file)

    print("\nCurrent repository = ",each_repository,"\tcurrent results file = ",curr_results_file,"\tsize of the current results file = ",len(curr_rep_df))

    ##-- Get the controlled vocabularies
    if each_repository == 'vdjserver':
        subject_id = 'ir_project_sample_id'
        sample_id = 'rearrangement_set_id'
        productive = 'productive'
        junction_nuc = 'junction'
        junction_aa = 'junction_aa'
    else:
        # BDC subject_id = 'subject_id'
        subject_id = 'sample_id'
        sample_id = 'sample_id'
        productive = 'productive'
        junction_nuc = 'junction'
        junction_aa = 'junction_aa'

    for curr_recno in range(0,len(curr_rep_df)):
        
        # curr_uniq_sampid = curr_rep_df[subject_id][curr_recno] + "_" + curr_rep_df[sample_id][curr_recno]
        curr_uniq_sampid = each_repository + "_" + str(str(curr_rep_df[subject_id][curr_recno]).strip()) + "_" + str(str(curr_rep_df[sample_id][curr_recno]).strip())

        uniq_sample_metadata[curr_uniq_sampid]['repository'] = each_repository
        uniq_sample_metadata[curr_uniq_sampid]['subject_id'] = curr_rep_df[subject_id][curr_recno]
        uniq_sample_metadata[curr_uniq_sampid]['sample_id'] = curr_rep_df[sample_id][curr_recno]

        if functional_filter:
            if curr_rep_df[productive][curr_recno]:
                if curr_uniq_sampid not in persample_junctions['aminos']:
                    # persample_junctions[curr_uniq_sampid] = collections.OrderedDict()
                    # persample_junctions['nucleotides'] = collections.OrderedDict()
                    # persample_junctions['aminos'] = collections.OrderedDict()
                    persample_junctions['nucleotides'][curr_uniq_sampid] = set()
                    persample_junctions['aminos'][curr_uniq_sampid] = set()
                
                persample_junctions['nucleotides'][curr_uniq_sampid].add(curr_rep_df[junction_nuc][curr_recno])
                persample_junctions['aminos'][curr_uniq_sampid].add(curr_rep_df[junction_aa][curr_recno])
        else:
            if curr_uniq_sampid not in persample_junctions['aminos']:
                # persample_junctions['nucleotides'] = collections.OrderedDict()
                # persample_junctions['aminos'] = collections.OrderedDict()
                persample_junctions['nucleotides'][curr_uniq_sampid] = set()
                persample_junctions['aminos'][curr_uniq_sampid] = set()
            
            persample_junctions['nucleotides'][curr_uniq_sampid].add(curr_rep_df[junction_nuc][curr_recno])
            persample_junctions['aminos'][curr_uniq_sampid].add(curr_rep_df[junction_aa][curr_recno])

if len(uniq_sample_metadata.keys()) > args.pairwise_limit:
    print("\nExceeded the pair wise sample limit.")
    exit()


print("\nThe list of unique sample ids are: ",list(persample_junctions['aminos'].keys()))

##-- Compare pair wise sample and get counts of shared junctions_aa

persample_junctions_aa_stats = collections.OrderedDict()
persample_junctions_aa_stats['aminos'] = collections.defaultdict(collections.OrderedDict)

persample_junctions_aa_stats['aminos']['samp1_v_samp2']['counts'] = collections.defaultdict(collections.OrderedDict)
persample_junctions_aa_stats['aminos']['samp1_v_samp2']['junctions'] = collections.defaultdict(collections.OrderedDict)

pw_samples = []
# pw1_samples = set()
# pw2_samples = set()

for each_uniqsample_1 in persample_junctions['aminos']:
    for each_uniqsample_2 in persample_junctions['aminos']:
        
        ##-- Check if previously compared
        if each_uniqsample_1 in persample_junctions_aa_stats['aminos']['samp1_v_samp2']['counts']:
            if each_uniqsample_2 in persample_junctions_aa_stats['aminos']['samp1_v_samp2']['counts'][each_uniqsample_1]:
                continue
            else:
                persample_junctions_aa_stats['aminos']['samp1_v_samp2']['counts'][each_uniqsample_1][each_uniqsample_2] = 0
                persample_junctions_aa_stats['aminos']['samp1_v_samp2']['junctions'][each_uniqsample_1][each_uniqsample_2] = set()
        elif each_uniqsample_2 in persample_junctions_aa_stats['aminos']['samp1_v_samp2']['counts']:
            if each_uniqsample_1 in persample_junctions_aa_stats['aminos']['samp1_v_samp2']['counts'][each_uniqsample_2]:
                continue
            else:
                persample_junctions_aa_stats['aminos']['samp1_v_samp2']['counts'][each_uniqsample_2][each_uniqsample_1] = 0
                persample_junctions_aa_stats['aminos']['samp1_v_samp2']['junctions'][each_uniqsample_2][each_uniqsample_1] = set()
        
        pw_samples = pw_samples + [(each_uniqsample_1, each_uniqsample_2),]
        # pw1_samples.add(each_uniqsample_1)
        # pw2_samples.add(each_uniqsample_2)
        

        if each_uniqsample_1 == each_uniqsample_2:
            persample_junctions_aa_stats['aminos']['samp1_v_samp2']['counts'][each_uniqsample_1][each_uniqsample_2] = len(persample_junctions['aminos'][each_uniqsample_1])
            persample_junctions_aa_stats['aminos']['samp1_v_samp2']['junctions'][each_uniqsample_1][each_uniqsample_2] = persample_junctions['aminos'][each_uniqsample_1]
            continue

        for eachsamp1_junction_aa in persample_junctions['aminos'][each_uniqsample_1]:
            if eachsamp1_junction_aa in persample_junctions['aminos'][each_uniqsample_2]:                
                persample_junctions_aa_stats['aminos']['samp1_v_samp2']['counts'][each_uniqsample_1][each_uniqsample_2] += 1
                persample_junctions_aa_stats['aminos']['samp1_v_samp2']['junctions'][each_uniqsample_1][each_uniqsample_2].add(eachsamp1_junction_aa)

# with open(pwstats_junctions_filenm,'w') as pwstats_junctions_file:

#     pwstats_junctions_file.write()

# for eachsample in persample_junctions_aa_stats['aminos']['samp1_v_samp2']['counts']:
for eachsample in persample_junctions['aminos']:
    print("\t", eachsample, sep='', end = '', file = pwstats_junctions_file)
    
print("\nPair wise samples are: ",pw_samples,"\tsamples 1 vs samples 2 = ",list(persample_junctions_aa_stats['aminos']['samp1_v_samp2']['counts'].keys()))

heatmap_aa_x_data = []
heatmap_aa_y_data = []
heatmap_aa_z_data = []

# for eachsample1 in persample_junctions_aa_stats['aminos']['samp1_v_samp2']['counts']:
for eachsample1 in persample_junctions['aminos']:    
    print("\n", eachsample1, sep='', end = '', file = pwstats_junctions_file)
    print("\nline 187:eachsample1 = ",eachsample1,"\tsamples 1",list(persample_junctions_aa_stats['aminos']['samp1_v_samp2']['counts'].keys()),"\tsamples 2: ",list(persample_junctions_aa_stats['aminos']['samp1_v_samp2']['counts'][eachsample1].keys()))
    heatmap_aa_x_data = heatmap_aa_x_data + [eachsample1,]
    heatmap_aa_y_data = []
    temp_z_data = []
    temp_shared_junction_aa = set()
    for eachsample2 in persample_junctions['aminos']:
    # for eachsample2 in persample_junctions_aa_stats['aminos']['samp1_v_samp2']['counts']:
        # print("\nline 189: eachsample1, eachsample2 are: ",(eachsample1, eachsample2))
        
        heatmap_aa_y_data = heatmap_aa_y_data + [eachsample2,]
        if (eachsample1, eachsample2) in pw_samples:
            print("\t",persample_junctions_aa_stats['aminos']['samp1_v_samp2']['counts'][eachsample1][eachsample2], sep = '', end = '', file = pwstats_junctions_file)
            temp_z_data = temp_z_data + [persample_junctions_aa_stats['aminos']['samp1_v_samp2']['counts'][eachsample1][eachsample2],]
            temp_shared_junction_aa = persample_junctions_aa_stats['aminos']['samp1_v_samp2']['junctions'][eachsample1][eachsample2]


            if eachsample1 != eachsample2:
                shared_junction_aa_file = open(args.output_dir + "/" + eachsample1 + "_vs_" + eachsample2 + '_shared_junction_aa' + "_" + str(args.outfile_tag) + ".txt",'w')
                
                print("list of ",len(temp_shared_junction_aa)," junction amino acid sequences shared between ",eachsample1," and ",eachsample2,sep = '', file = shared_junction_aa_file)
                for each_junctionaa_seq in temp_shared_junction_aa:
                    print(each_junctionaa_seq,file = shared_junction_aa_file)
                shared_junction_aa_file.close()

        else:
            print("\t",persample_junctions_aa_stats['aminos']['samp1_v_samp2']['counts'][eachsample2][eachsample1], sep = '', end = '', file = pwstats_junctions_file)
            temp_z_data = temp_z_data + [persample_junctions_aa_stats['aminos']['samp1_v_samp2']['counts'][eachsample2][eachsample1],]
            temp_shared_junction_aa = persample_junctions_aa_stats['aminos']['samp1_v_samp2']['junctions'][eachsample2][eachsample1]


    heatmap_aa_z_data = heatmap_aa_z_data + [temp_z_data,]

    

pwstats_junctions_file.close()


# trace = go.Heatmap(z=[[1, 20, 30, 50, 1], [20, 1, 60, 80, 30], [30, 60, 1, -10, 20]],
#                    x=['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
#                    y=['Morning', 'Afternoon', 'Evening'])

trace = go.Heatmap(z= heatmap_aa_z_data,
                   x= heatmap_aa_x_data,
                   y= heatmap_aa_y_data)

trace_data=[trace]

plottitle = "Pairwise distribution of number of amino acid junction sequences for the selected samples"

# layout = go.Layout(title = plottitle, titlefont = dict(size = 24), margin = dict(t=50, b = 100), hovermode = "closest", 
# xaxis=dict(tickangle=-45, title = "Samples", titlefont = dict(size = 22), tickfont = dict(size = 14)), 
# yaxis=dict(title = "Samples", titlefont = dict(size = 22), tickfont = dict(size = 18)), 
# legend = dict(x=-0.1, y=-0.2, orientation="h", font = dict(size = 18)))

layout = go.Layout(title = plottitle, hovermode = "closest",
xaxis=dict(tickangle=0, title = "Samples"), 
yaxis=dict(tickangle=0, title = "Samples"), 
legend = dict(x=-0.1, y=-0.2, orientation="h"))

# fig = go.Figure(data=trace_data, layout=layout)
fig = ff.create_annotated_heatmap(heatmap_aa_z_data, x=heatmap_aa_x_data, y=heatmap_aa_y_data, annotation_text=heatmap_aa_z_data)

fig.layout.title = plottitle
fig.layout.xaxis = dict(tickangle=-45, title = "Samples")
fig.layout.yaxis = dict(tickangle=0, title = "Samples")
fig.layout.legend = dict(x=-0.1, y=-0.2, orientation="h")

heatmapfilenm = args.output_dir + "/" + 'heatmap_pairwise_shared_junction_aa' + "_" + str(args.outfile_tag)
offly.plot(fig, filename=(heatmapfilenm + ".html"), auto_open = False)


# offly.iplot(data, filename='heatmap_pairwise_shared_junction_aa')
# py.iplot(data, filename='heatmap_pairwise_shared_junction_aa')




