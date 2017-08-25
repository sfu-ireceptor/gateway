$(document).ready(function() {
	$('#summary_charts').each(function(){
		$.getJSON('samples/json', function(data) {
			showData(data);
		});
	});
});

/**********************************************************
* Test data
**********************************************************/

// An example data set that should be equivalent of the data from
// a call to the /v1/samples API on the IPA repository with 4 samples AND a call
// to the /v1/samples API on the JamieLab repository.
var v1SamplesSummaryCombined =
[
{"subject_code":"Pooled mice control SI PBS","subject_id":13,"subject_gender":null,"subject_species":"Mouse","subject_ethnicity":"","project_id":1,"project_name":"Mouse single immunization","project_parent_id":-1,"sra_accession":null,"lab_id":1,"lab_name":"Jamie Scott Lab","disease_state_id":null,"disease_state_name":null,"case_control_id":1,"case_control_name":"Case","sample_id":1,"project_sample_id":1,"sequence_count":28619,"project_sample_note":"Controls using PBS immunization","sra_run_id":null,"sample_name":"3-PC-B","subject_age":null,"sample_subject_id":13,"dna_id":1,"dna_type":"cDNA","sample_source_id":2,"sample_source_name":"Blood","lab_cell_subset_name":"Plasma Cell","ireceptor_cell_subset_name":"Plasma Cell","marker_1":"CD138+","marker_2":null,"marker_3":null,"marker_4":null,"marker_5":null,"marker_6":null},
{"subject_code":"Pooled mice control SI PBS","subject_id":13,"subject_gender":null,"subject_species":"Mouse","subject_ethnicity":"","project_id":1,"project_name":"Mouse single immunization","project_parent_id":-1,"sra_accession":null,"lab_id":1,"lab_name":"Jamie Scott Lab","disease_state_id":null,"disease_state_name":null,"case_control_id":1,"case_control_name":"Case","sample_id":75,"project_sample_id":75,"sequence_count":115348,"project_sample_note":"Controls using PBS immunization","sra_run_id":null,"sample_name":"C-BPB-POOL","subject_age":null,"sample_subject_id":13,"dna_id":1,"dna_type":"cDNA","sample_source_id":2,"sample_source_name":"Blood","lab_cell_subset_name":"Plasma Cell","ireceptor_cell_subset_name":"Plasma Cell","marker_1":"CD138+","marker_2":null,"marker_3":null,"marker_4":null,"marker_5":null,"marker_6":null},
{"subject_code":"Control SI PBS M1","subject_id":15,"subject_gender":null,"subject_species":"Mouse","subject_ethnicity":"","project_id":1,"project_name":"Mouse single immunization","project_parent_id":-1,"sra_accession":null,"lab_id":1,"lab_name":"Jamie Scott Lab","disease_state_id":null,"disease_state_name":null,"case_control_id":1,"case_control_name":"Case","sample_id":2,"project_sample_id":2,"sequence_count":0,"project_sample_note":"Controls using PBS immunization","sra_run_id":null,"sample_name":"3-PC-M1","subject_age":null,"sample_subject_id":15,"dna_id":1,"dna_type":"cDNA","sample_source_id":3,"sample_source_name":"Spleen","lab_cell_subset_name":"Plasma Cell","ireceptor_cell_subset_name":"Plasma Cell","marker_1":"CD138+","marker_2":null,"marker_3":null,"marker_4":null,"marker_5":null,"marker_6":null},
{"subject_code":"Control SI PBS M1","subject_id":15,"subject_gender":null,"subject_species":"Mouse","subject_ethnicity":"","project_id":1,"project_name":"Mouse single immunization","project_parent_id":-1,"sra_accession":null,"lab_id":1,"lab_name":"Jamie Scott Lab","disease_state_id":null,"disease_state_name":null,"case_control_id":1,"case_control_name":"Case","sample_id":77,"project_sample_id":77,"sequence_count":89413,"project_sample_note":"Controls using PBS immunization","sra_run_id":null,"sample_name":"C-SPPB-1","subject_age":null,"sample_subject_id":15,"dna_id":1,"dna_type":"cDNA","sample_source_id":3,"sample_source_name":"Spleen","lab_cell_subset_name":"Plasma Cell","ireceptor_cell_subset_name":"Plasma Cell","marker_1":"CD138+","marker_2":null,"marker_3":null,"marker_4":null,"marker_5":null,"marker_6":null},
{"subject_code":"Control SI PBS M2","subject_id":16,"subject_gender":null,"subject_species":"Mouse","subject_ethnicity":"","project_id":1,"project_name":"Mouse single immunization","project_parent_id":-1,"sra_accession":null,"lab_id":1,"lab_name":"Jamie Scott Lab","disease_state_id":null,"disease_state_name":null,"case_control_id":1,"case_control_name":"Case","sample_id":3,"project_sample_id":3,"sequence_count":0,"project_sample_note":"Controls using PBS immunization","sra_run_id":null,"sample_name":"3-PC-M2","subject_age":null,"sample_subject_id":16,"dna_id":1,"dna_type":"cDNA","sample_source_id":3,"sample_source_name":"Spleen","lab_cell_subset_name":"Plasma Cell","ireceptor_cell_subset_name":"Plasma Cell","marker_1":"CD138+","marker_2":null,"marker_3":null,"marker_4":null,"marker_5":null,"marker_6":null},
{"subject_code":"Control SI PBS M2","subject_id":16,"subject_gender":null,"subject_species":"Mouse","subject_ethnicity":"","project_id":1,"project_name":"Mouse single immunization","project_parent_id":-1,"sra_accession":null,"lab_id":1,"lab_name":"Jamie Scott Lab","disease_state_id":null,"disease_state_name":null,"case_control_id":1,"case_control_name":"Case","sample_id":78,"project_sample_id":78,"sequence_count":118414,"project_sample_note":"Controls using PBS immunization","sra_run_id":null,"sample_name":"C-SPPB-2","subject_age":null,"sample_subject_id":16,"dna_id":1,"dna_type":"cDNA","sample_source_id":3,"sample_source_name":"Spleen","lab_cell_subset_name":"Plasma Cell","ireceptor_cell_subset_name":"Plasma Cell","marker_1":"CD138+","marker_2":null,"marker_3":null,"marker_4":null,"marker_5":null,"marker_6":null},
{"subject_code":"Pooled mice SI Phage 9d","subject_id":17,"subject_gender":null,"subject_species":"Mouse","subject_ethnicity":"","project_id":1,"project_name":"Mouse single immunization","project_parent_id":-1,"sra_accession":null,"lab_id":1,"lab_name":"Jamie Scott Lab","disease_state_id":null,"disease_state_name":null,"case_control_id":1,"case_control_name":"Case","sample_id":4,"project_sample_id":4,"sequence_count":105197,"project_sample_note":"Single immunization with pure phage","sra_run_id":null,"sample_name":"1-AS-B9","subject_age":null,"sample_subject_id":17,"dna_id":1,"dna_type":"cDNA","sample_source_id":2,"sample_source_name":"Blood","lab_cell_subset_name":"Antigen Specific B Cell","ireceptor_cell_subset_name":"Mature B Cell","marker_1":"CD19+","marker_2":"CD138-","marker_3":"pVIII-peptide+","marker_4":null,"marker_5":null,"marker_6":null},{"subject_code":"Pooled mice SI Phage 9d","subject_id":17,"subject_gender":null,"subject_species":"Mouse","subject_ethnicity":"","project_id":1,"project_name":"Mouse single immunization","project_parent_id":-1,"sra_accession":null,"lab_id":1,"lab_name":"Jamie Scott Lab","disease_state_id":null,"disease_state_name":null,"case_control_id":1,"case_control_name":"Case","sample_id":16,"project_sample_id":16,"sequence_count":121521,"project_sample_note":"Single immunization with pure phage","sra_run_id":null,"sample_name":"1-PC-B9","subject_age":null,"sample_subject_id":17,"dna_id":1,"dna_type":"cDNA","sample_source_id":2,"sample_source_name":"Blood","lab_cell_subset_name":"Plasma Cell","ireceptor_cell_subset_name":"Plasma Cell","marker_1":"CD138+","marker_2":null,"marker_3":null,"marker_4":null,"marker_5":null,"marker_6":null},{"subject_code":"Pooled mice SI Phage 9d","subject_id":17,"subject_gender":null,"subject_species":"Mouse","subject_ethnicity":"","project_id":1,"project_name":"Mouse single immunization","project_parent_id":-1,"sra_accession":null,"lab_id":1,"lab_name":"Jamie Scott Lab","disease_state_id":null,"disease_state_name":null,"case_control_id":1,"case_control_name":"Case","sample_id":90,"project_sample_id":90,"sequence_count":39190,"project_sample_note":"Single immunization with pure phage","sra_run_id":null,"sample_name":"1-BPB-9","subject_age":null,"sample_subject_id":17,"dna_id":1,"dna_type":"cDNA","sample_source_id":2,"sample_source_name":"Blood","lab_cell_subset_name":"Plasma Cell","ireceptor_cell_subset_name":"Plasma Cell","marker_1":"CD138+","marker_2":null,"marker_3":null,"marker_4":null,"marker_5":null,"marker_6":null},{"subject_code":"SI Phage M17","subject_id":18,"subject_gender":null,"subject_species":"Mouse","subject_ethnicity":"","project_id":1,"project_name":"Mouse single immunization","project_parent_id":-1,"sra_accession":null,"lab_id":1,"lab_name":"Jamie Scott Lab","disease_state_id":null,"disease_state_name":null,"case_control_id":1,"case_control_name":"Case","sample_id":5,"project_sample_id":5,"sequence_count":184797,"project_sample_note":"Single immunization with pure phage","sra_run_id":null,"sample_name":"1-AS-M17","subject_age":null,"sample_subject_id":18,"dna_id":1,"dna_type":"cDNA","sample_source_id":3,"sample_source_name":"Spleen","lab_cell_subset_name":"Antigen Specific B Cell","ireceptor_cell_subset_name":"Mature B Cell","marker_1":"CD19+","marker_2":"CD138-","marker_3":"pVIII-peptide+","marker_4":null,"marker_5":null,"marker_6":null},{"subject_code":"SI Phage M17","subject_id":18,"subject_gender":null,"subject_species":"Mouse","subject_ethnicity":"","project_id":1,"project_name":"Mouse single immunization","project_parent_id":-1,"sra_accession":null,"lab_id":1,"lab_name":"Jamie Scott Lab","disease_state_id":null,"disease_state_name":null,"case_control_id":1,"case_control_name":"Case","sample_id":17,"project_sample_id":17,"sequence_count":112700,"project_sample_note":"Single immunization with pure phage","sra_run_id":null,"sample_name":"1-PC-M17","subject_age":null,"sample_subject_id":18,"dna_id":1,"dna_type":"cDNA","sample_source_id":3,"sample_source_name":"Spleen","lab_cell_subset_name":"Plasma Cell","ireceptor_cell_subset_name":"Plasma Cell","marker_1":"CD138+","marker_2":null,"marker_3":null,"marker_4":null,"marker_5":null,"marker_6":null},{"subject_code":"SI Phage M17","subject_id":18,"subject_gender":null,"subject_species":"Mouse","subject_ethnicity":"","project_id":1,"project_name":"Mouse single immunization","project_parent_id":-1,"sra_accession":null,"lab_id":1,"lab_name":"Jamie Scott Lab","disease_state_id":null,"disease_state_name":null,"case_control_id":1,"case_control_name":"Case","sample_id":79,"project_sample_id":79,"sequence_count":31847,"project_sample_note":"Single immunization with pure phage","sra_run_id":null,"sample_name":"1-SPAg-2-17","subject_age":null,"sample_subject_id":18,"dna_id":1,"dna_type":"cDNA","sample_source_id":3,"sample_source_name":"Spleen","lab_cell_subset_name":"Antigen Specific B Cell","ireceptor_cell_subset_name":"Mature B Cell","marker_1":"CD19+","marker_2":"CD138-","marker_3":"pVIII-peptide+","marker_4":null,"marker_5":null,"marker_6":null},{"subject_code":"SI Phage M17","subject_id":18,"subject_gender":null,"subject_species":"Mouse","subject_ethnicity":"","project_id":1,"project_name":"Mouse single immunization","project_parent_id":-1,"sra_accession":null,"lab_id":1,"lab_name":"Jamie Scott Lab","disease_state_id":null,"disease_state_name":null,"case_control_id":1,"case_control_name":"Case","sample_id":91,"project_sample_id":91,"sequence_count":167162,"project_sample_note":"Single immunization with pure phage","sra_run_id":null,"sample_name":"1-SPPB-2-17","subject_age":null,"sample_subject_id":18,"dna_id":1,"dna_type":"cDNA","sample_source_id":3,"sample_source_name":"Spleen","lab_cell_subset_name":"Plasma Cell","ireceptor_cell_subset_name":"Plasma Cell","marker_1":"CD138+","marker_2":null,"marker_3":null,"marker_4":null,"marker_5":null,"marker_6":null},{"subject_code":"SI Phage M18","subject_id":19,"subject_gender":null,"subject_species":"Mouse","subject_ethnicity":"","project_id":1,"project_name":"Mouse single immunization","project_parent_id":-1,"sra_accession":null,"lab_id":1,"lab_name":"Jamie Scott Lab","disease_state_id":null,"disease_state_name":null,"case_control_id":1,"case_control_name":"Case","sample_id":6,"project_sample_id":6,"sequence_count":179577,"project_sample_note":"Single immunization with pure phage","sra_run_id":null,"sample_name":"1-AS-M18","subject_age":null,"sample_subject_id":19,"dna_id":1,"dna_type":"cDNA","sample_source_id":3,"sample_source_name":"Spleen","lab_cell_subset_name":"Antigen Specific B Cell","ireceptor_cell_subset_name":"Mature B Cell","marker_1":"CD19+","marker_2":"CD138-","marker_3":"pVIII-peptide+","marker_4":null,"marker_5":null,"marker_6":null},{"subject_code":"SI Phage M18","subject_id":19,"subject_gender":null,"subject_species":"Mouse","subject_ethnicity":"","project_id":1,"project_name":"Mouse single immunization","project_parent_id":-1,"sra_accession":null,"lab_id":1,"lab_name":"Jamie Scott Lab","disease_state_id":null,"disease_state_name":null,"case_control_id":1,"case_control_name":"Case","sample_id":18,"project_sample_id":18,"sequence_count":133034,"project_sample_note":"Single immunization with pure phage","sra_run_id":null,"sample_name":"1-PC-M18","subject_age":null,"sample_subject_id":19,"dna_id":1,"dna_type":"cDNA","sample_source_id":3,"sample_source_name":"Spleen","lab_cell_subset_name":"Plasma Cell","ireceptor_cell_subset_name":"Plasma Cell","marker_1":"CD138+","marker_2":null,"marker_3":null,"marker_4":null,"marker_5":null,"marker_6":null},{"subject_code":"SI Phage M18","subject_id":19,"subject_gender":null,"subject_species":"Mouse","subject_ethnicity":"","project_id":1,"project_name":"Mouse single immunization","project_parent_id":-1,"sra_accession":null,"lab_id":1,"lab_name":"Jamie Scott Lab","disease_state_id":null,"disease_state_name":null,"case_control_id":1,"case_control_name":"Case","sample_id":80,"project_sample_id":80,"sequence_count":56226,"project_sample_note":"Single immunization with pure phage","sra_run_id":null,"sample_name":"1-SPAg-2-18","subject_age":null,"sample_subject_id":19,"dna_id":1,"dna_type":"cDNA","sample_source_id":3,"sample_source_name":"Spleen","lab_cell_subset_name":"Antigen Specific B Cell","ireceptor_cell_subset_name":"Mature B Cell","marker_1":"CD19+","marker_2":"CD138-","marker_3":"pVIII-peptide+","marker_4":null,"marker_5":null,"marker_6":null},{"subject_code":"SI Phage M18","subject_id":19,"subject_gender":null,"subject_species":"Mouse","subject_ethnicity":"","project_id":1,"project_name":"Mouse single immunization","project_parent_id":-1,"sra_accession":null,"lab_id":1,"lab_name":"Jamie Scott Lab","disease_state_id":null,"disease_state_name":null,"case_control_id":1,"case_control_name":"Case","sample_id":92,"project_sample_id":92,"sequence_count":148812,"project_sample_note":"Single immunization with pure phage","sra_run_id":null,"sample_name":"1-SPPB-2-18","subject_age":null,"sample_subject_id":19,"dna_id":1,"dna_type":"cDNA","sample_source_id":3,"sample_source_name":"Spleen","lab_cell_subset_name":"Plasma Cell","ireceptor_cell_subset_name":"Plasma Cell","marker_1":"CD138+","marker_2":null,"marker_3":null,"marker_4":null,"marker_5":null,"marker_6":null},{"subject_code":"SI Phage M13","subject_id":20,"subject_gender":null,"subject_species":"Mouse","subject_ethnicity":"","project_id":1,"project_name":"Mouse single immunization","project_parent_id":-1,"sra_accession":null,"lab_id":1,"lab_name":"Jamie Scott Lab","disease_state_id":null,"disease_state_name":null,"case_control_id":1,"case_control_name":"Case","sample_id":7,"project_sample_id":7,"sequence_count":200943,"project_sample_note":"Single immunization with pure phage","sra_run_id":null,"sample_name":"1-AS-M13","subject_age":null,"sample_subject_id":20,"dna_id":1,"dna_type":"cDNA","sample_source_id":3,"sample_source_name":"Spleen","lab_cell_subset_name":"Antigen Specific B Cell","ireceptor_cell_subset_name":"Mature B Cell","marker_1":"CD19+","marker_2":"CD138-","marker_3":"pVIII-peptide+","marker_4":null,"marker_5":null,"marker_6":null},{"subject_code":"SI Phage M13","subject_id":20,"subject_gender":null,"subject_species":"Mouse","subject_ethnicity":"","project_id":1,"project_name":"Mouse single immunization","project_parent_id":-1,"sra_accession":null,"lab_id":1,"lab_name":"Jamie Scott Lab","disease_state_id":null,"disease_state_name":null,"case_control_id":1,"case_control_name":"Case","sample_id":21,"project_sample_id":21,"sequence_count":169683,"project_sample_note":"Single immunization with pure phage","sra_run_id":null,"sample_name":"1-PC-M13","subject_age":null,"sample_subject_id":20,"dna_id":1,"dna_type":"cDNA","sample_source_id":3,"sample_source_name":"Spleen","lab_cell_subset_name":"Plasma Cell","ireceptor_cell_subset_name":"Plasma Cell","marker_1":"CD138+","marker_2":null,"marker_3":null,"marker_4":null,"marker_5":null,"marker_6":null},{"subject_code":"SI Phage M13","subject_id":20,"subject_gender":null,"subject_species":"Mouse","subject_ethnicity":"","project_id":1,"project_name":"Mouse single immunization","project_parent_id":-1,"sra_accession":null,"lab_id":1,"lab_name":"Jamie Scott Lab","disease_state_id":null,"disease_state_name":null,"case_control_id":1,"case_control_name":"Case","sample_id":81,"project_sample_id":81,"sequence_count":57025,"project_sample_note":"Single immunization with pure phage","sra_run_id":null,"sample_name":"1-SPAg-4-13","subject_age":null,"sample_subject_id":20,"dna_id":1,"dna_type":"cDNA","sample_source_id":3,"sample_source_name":"Spleen","lab_cell_subset_name":"Antigen Specific B Cell","ireceptor_cell_subset_name":"Mature B Cell","marker_1":"CD19+","marker_2":"CD138-","marker_3":"pVIII-peptide+","marker_4":null,"marker_5":null,"marker_6":null},{"subject_code":"SI Phage M13","subject_id":20,"subject_gender":null,"subject_species":"Mouse","subject_ethnicity":"","project_id":1,"project_name":"Mouse single immunization","project_parent_id":-1,"sra_accession":null,"lab_id":1,"lab_name":"Jamie Scott Lab","disease_state_id":null,"disease_state_name":null,"case_control_id":1,"case_control_name":"Case","sample_id":95,"project_sample_id":95,"sequence_count":122820,"project_sample_note":"Single immunization with pure phage","sra_run_id":null,"sample_name":"1-SPPB-4-13","subject_age":null,"sample_subject_id":20,"dna_id":1,"dna_type":"cDNA","sample_source_id":3,"sample_source_name":"Spleen","lab_cell_subset_name":"Plasma Cell","ireceptor_cell_subset_name":"Plasma Cell","marker_1":"CD138+","marker_2":null,"marker_3":null,"marker_4":null,"marker_5":null,"marker_6":null},{"subject_code":"SI Phage M14","subject_id":21,"subject_gender":null,"subject_species":"Mouse","subject_ethnicity":"","project_id":1,"project_name":"Mouse single immunization","project_parent_id":-1,"sra_accession":null,"lab_id":1,"lab_name":"Jamie Scott Lab","disease_state_id":null,"disease_state_name":null,"case_control_id":1,"case_control_name":"Case","sample_id":8,"project_sample_id":8,"sequence_count":179739,"project_sample_note":"Single immunization with pure phage","sra_run_id":null,"sample_name":"1-AS-M14","subject_age":null,"sample_subject_id":21,"dna_id":1,"dna_type":"cDNA","sample_source_id":3,"sample_source_name":"Spleen","lab_cell_subset_name":"Antigen Specific B Cell","ireceptor_cell_subset_name":"Mature B Cell","marker_1":"CD19+","marker_2":"CD138-","marker_3":"pVIII-peptide+","marker_4":null,"marker_5":null,"marker_6":null},{"subject_code":"SI Phage M14","subject_id":21,"subject_gender":null,"subject_species":"Mouse","subject_ethnicity":"","project_id":1,"project_name":"Mouse single immunization","project_parent_id":-1,"sra_accession":null,"lab_id":1,"lab_name":"Jamie Scott Lab","disease_state_id":null,"disease_state_name":null,"case_control_id":1,"case_control_name":"Case","sample_id":22,"project_sample_id":22,"sequence_count":146527,"project_sample_note":"Single immunization with pure phage","sra_run_id":null,"sample_name":"1-PC-M14","subject_age":null,"sample_subject_id":21,"dna_id":1,"dna_type":"cDNA","sample_source_id":3,"sample_source_name":"Spleen","lab_cell_subset_name":"Plasma Cell","ireceptor_cell_subset_name":"Plasma Cell","marker_1":"CD138+","marker_2":null,"marker_3":null,"marker_4":null,"marker_5":null,"marker_6":null},{"subject_code":"SI Phage M14","subject_id":21,"subject_gender":null,"subject_species":"Mouse","subject_ethnicity":"","project_id":1,"project_name":"Mouse single immunization","project_parent_id":-1,"sra_accession":null,"lab_id":1,"lab_name":"Jamie Scott Lab","disease_state_id":null,"disease_state_name":null,"case_control_id":1,"case_control_name":"Case","sample_id":82,"project_sample_id":82,"sequence_count":84682,"project_sample_note":"Single immunization with pure phage","sra_run_id":null,"sample_name":"1-SPAg-4-14","subject_age":null,"sample_subject_id":21,"dna_id":1,"dna_type":"cDNA","sample_source_id":3,"sample_source_name":"Spleen","lab_cell_subset_name":"Antigen Specific B Cell","ireceptor_cell_subset_name":"Mature B Cell","marker_1":"CD19+","marker_2":"CD138-","marker_3":"pVIII-peptide+","marker_4":null,"marker_5":null,"marker_6":null},{"subject_code":"SI Phage M14","subject_id":21,"subject_gender":null,"subject_species":"Mouse","subject_ethnicity":"","project_id":1,"project_name":"Mouse single immunization","project_parent_id":-1,"sra_accession":null,"lab_id":1,"lab_name":"Jamie Scott Lab","disease_state_id":null,"disease_state_name":null,"case_control_id":1,"case_control_name":"Case","sample_id":96,"project_sample_id":96,"sequence_count":50000,"project_sample_note":"Single immunization with pure phage","sra_run_id":null,"sample_name":"1-SPPB-4-14","subject_age":null,"sample_subject_id":21,"dna_id":1,"dna_type":"cDNA","sample_source_id":3,"sample_source_name":"Spleen","lab_cell_subset_name":"Plasma Cell","ireceptor_cell_subset_name":"Plasma Cell","marker_1":"CD138+","marker_2":null,"marker_3":null,"marker_4":null,"marker_5":null,"marker_6":null},{"subject_code":"SI Phage M15","subject_id":22,"subject_gender":null,"subject_species":"Mouse","subject_ethnicity":"","project_id":1,"project_name":"Mouse single immunization","project_parent_id":-1,"sra_accession":null,"lab_id":1,"lab_name":"Jamie Scott Lab","disease_state_id":null,"disease_state_name":null,"case_control_id":1,"case_control_name":"Case","sample_id":9,"project_sample_id":9,"sequence_count":202749,"project_sample_note":"Single immunization with pure phage","sra_run_id":null,"sample_name":"1-AS-M15","subject_age":null,"sample_subject_id":22,"dna_id":1,"dna_type":"cDNA","sample_source_id":3,"sample_source_name":"Spleen","lab_cell_subset_name":"Antigen Specific B Cell","ireceptor_cell_subset_name":"Mature B Cell","marker_1":"CD19+","marker_2":"CD138-","marker_3":"pVIII-peptide+","marker_4":null,"marker_5":null,"marker_6":null},{"subject_code":"SI Phage M15","subject_id":22,"subject_gender":null,"subject_species":"Mouse","subject_ethnicity":"","project_id":1,"project_name":"Mouse single immunization","project_parent_id":-1,"sra_accession":null,"lab_id":1,"lab_name":"Jamie Scott Lab","disease_state_id":null,"disease_state_name":null,"case_control_id":1,"case_control_name":"Case","sample_id":23,"project_sample_id":23,"sequence_count":132700,"project_sample_note":"Single immunization with pure phage","sra_run_id":null,"sample_name":"1-PC-M15","subject_age":null,"sample_subject_id":22,"dna_id":1,"dna_type":"cDNA","sample_source_id":3,"sample_source_name":"Spleen","lab_cell_subset_name":"Plasma Cell","ireceptor_cell_subset_name":"Plasma Cell","marker_1":"CD138+","marker_2":null,"marker_3":null,"marker_4":null,"marker_5":null,"marker_6":null},{"subject_code":"SI Phage M15","subject_id":22,"subject_gender":null,"subject_species":"Mouse","subject_ethnicity":"","project_id":1,"project_name":"Mouse single immunization","project_parent_id":-1,"sra_accession":null,"lab_id":1,"lab_name":"Jamie Scott Lab","disease_state_id":null,"disease_state_name":null,"case_control_id":1,"case_control_name":"Case","sample_id":83,"project_sample_id":83,"sequence_count":53425,"project_sample_note":"Single immunization with pure phage","sra_run_id":null,"sample_name":"1-SPAg-4-15","subject_age":null,"sample_subject_id":22,"dna_id":1,"dna_type":"cDNA","sample_source_id":3,"sample_source_name":"Spleen","lab_cell_subset_name":"Antigen Specific B Cell","ireceptor_cell_subset_name":"Mature B Cell","marker_1":"CD19+","marker_2":"CD138-","marker_3":"pVIII-peptide+","marker_4":null,"marker_5":null,"marker_6":null},{"subject_code":"SI Phage M15","subject_id":22,"subject_gender":null,"subject_species":"Mouse","subject_ethnicity":"","project_id":1,"project_name":"Mouse single immunization","project_parent_id":-1,"sra_accession":null,"lab_id":1,"lab_name":"Jamie Scott Lab","disease_state_id":null,"disease_state_name":null,"case_control_id":1,"case_control_name":"Case","sample_id":97,"project_sample_id":97,"sequence_count":133460,"project_sample_note":"Single immunization with pure phage","sra_run_id":null,"sample_name":"1-SPPB-4-15","subject_age":null,"sample_subject_id":22,"dna_id":1,"dna_type":"cDNA","sample_source_id":3,"sample_source_name":"Spleen","lab_cell_subset_name":"Plasma Cell","ireceptor_cell_subset_name":"Plasma Cell","marker_1":"CD138+","marker_2":null,"marker_3":null,"marker_4":null,"marker_5":null,"marker_6":null},{"subject_code":"SI Phage M5","subject_id":23,"subject_gender":null,"subject_species":"Mouse","subject_ethnicity":"","project_id":1,"project_name":"Mouse single immunization","project_parent_id":-1,"sra_accession":null,"lab_id":1,"lab_name":"Jamie Scott Lab","disease_state_id":null,"disease_state_name":null,"case_control_id":1,"case_control_name":"Case","sample_id":10,"project_sample_id":10,"sequence_count":93080,"project_sample_note":"Single immunization with pure phage","sra_run_id":null,"sample_name":"1-AS-M5","subject_age":null,"sample_subject_id":23,"dna_id":1,"dna_type":"cDNA","sample_source_id":3,"sample_source_name":"Spleen","lab_cell_subset_name":"Antigen Specific B Cell","ireceptor_cell_subset_name":"Mature B Cell","marker_1":"CD19+","marker_2":"CD138-","marker_3":"pVIII-peptide+","marker_4":null,"marker_5":null,"marker_6":null},{"subject_code":"SI Phage M5","subject_id":23,"subject_gender":null,"subject_species":"Mouse","subject_ethnicity":"","project_id":1,"project_name":"Mouse single immunization","project_parent_id":-1,"sra_accession":null,"lab_id":1,"lab_name":"Jamie Scott Lab","disease_state_id":null,"disease_state_name":null,"case_control_id":1,"case_control_name":"Case","sample_id":24,"project_sample_id":24,"sequence_count":178202,"project_sample_note":"Single immunization with pure phage","sra_run_id":null,"sample_name":"1-PC-M5","subject_age":null,"sample_subject_id":23,"dna_id":1,"dna_type":"cDNA","sample_source_id":3,"sample_source_name":"Spleen","lab_cell_subset_name":"Plasma Cell","ireceptor_cell_subset_name":"Plasma Cell","marker_1":"CD138+","marker_2":null,"marker_3":null,"marker_4":null,"marker_5":null,"marker_6":null},{"subject_code":"SI Phage M5","subject_id":23,"subject_gender":null,"subject_species":"Mouse","subject_ethnicity":"","project_id":1,"project_name":"Mouse single immunization","project_parent_id":-1,"sra_accession":null,"lab_id":1,"lab_name":"Jamie Scott Lab","disease_state_id":null,"disease_state_name":null,"case_control_id":1,"case_control_name":"Case","sample_id":84,"project_sample_id":84,"sequence_count":45035,"project_sample_note":"Single immunization with pure phage","sra_run_id":null,"sample_name":"1-SPAg-9-5","subject_age":null,"sample_subject_id":23,"dna_id":1,"dna_type":"cDNA","sample_source_id":3,"sample_source_name":"Spleen","lab_cell_subset_name":"Antigen Specific B Cell","ireceptor_cell_subset_name":"Mature B Cell","marker_1":"CD19+","marker_2":"CD138-","marker_3":"pVIII-peptide+","marker_4":null,"marker_5":null,"marker_6":null},{"subject_code":"SI Phage M5","subject_id":23,"subject_gender":null,"subject_species":"Mouse","subject_ethnicity":"","project_id":1,"project_name":"Mouse single immunization","project_parent_id":-1,"sra_accession":null,"lab_id":1,"lab_name":"Jamie Scott Lab","disease_state_id":null,"disease_state_name":null,"case_control_id":1,"case_control_name":"Case","sample_id":98,"project_sample_id":98,"sequence_count":108754,"project_sample_note":"Single immunization with pure phage","sra_run_id":null,"sample_name":"1-SPPB-9-5","subject_age":null,"sample_subject_id":23,"dna_id":1,"dna_type":"cDNA","sample_source_id":3,"sample_source_name":"Spleen","lab_cell_subset_name":"Plasma Cell","ireceptor_cell_subset_name":"Plasma Cell","marker_1":"CD138+","marker_2":null,"marker_3":null,"marker_4":null,"marker_5":null,"marker_6":null},{"subject_code":"SI Phage M6","subject_id":24,"subject_gender":null,"subject_species":"Mouse","subject_ethnicity":"","project_id":1,"project_name":"Mouse single immunization","project_parent_id":-1,"sra_accession":null,"lab_id":1,"lab_name":"Jamie Scott Lab","disease_state_id":null,"disease_state_name":null,"case_control_id":1,"case_control_name":"Case","sample_id":11,"project_sample_id":11,"sequence_count":123603,"project_sample_note":"Single immunization with pure phage","sra_run_id":null,"sample_name":"1-AS-M6","subject_age":null,"sample_subject_id":24,"dna_id":1,"dna_type":"cDNA","sample_source_id":3,"sample_source_name":"Spleen","lab_cell_subset_name":"Antigen Specific B Cell","ireceptor_cell_subset_name":"Mature B Cell","marker_1":"CD19+","marker_2":"CD138-","marker_3":"pVIII-peptide+","marker_4":null,"marker_5":null,"marker_6":null},{"subject_code":"SI Phage M6","subject_id":24,"subject_gender":null,"subject_species":"Mouse","subject_ethnicity":"","project_id":1,"project_name":"Mouse single immunization","project_parent_id":-1,"sra_accession":null,"lab_id":1,"lab_name":"Jamie Scott Lab","disease_state_id":null,"disease_state_name":null,"case_control_id":1,"case_control_name":"Case","sample_id":25,"project_sample_id":25,"sequence_count":171799,"project_sample_note":"Single immunization with pure phage","sra_run_id":null,"sample_name":"1-PC-M6","subject_age":null,"sample_subject_id":24,"dna_id":1,"dna_type":"cDNA","sample_source_id":3,"sample_source_name":"Spleen","lab_cell_subset_name":"Plasma Cell","ireceptor_cell_subset_name":"Plasma Cell","marker_1":"CD138+","marker_2":null,"marker_3":null,"marker_4":null,"marker_5":null,"marker_6":null},{"subject_code":"SI Phage M6","subject_id":24,"subject_gender":null,"subject_species":"Mouse","subject_ethnicity":"","project_id":1,"project_name":"Mouse single immunization","project_parent_id":-1,"sra_accession":null,"lab_id":1,"lab_name":"Jamie Scott Lab","disease_state_id":null,"disease_state_name":null,"case_control_id":1,"case_control_name":"Case","sample_id":85,"project_sample_id":85,"sequence_count":72838,"project_sample_note":"Single immunization with pure phage","sra_run_id":null,"sample_name":"1-SPAg-9-6","subject_age":null,"sample_subject_id":24,"dna_id":1,"dna_type":"cDNA","sample_source_id":3,"sample_source_name":"Spleen","lab_cell_subset_name":"Antigen Specific B Cell","ireceptor_cell_subset_name":"Mature B Cell","marker_1":"CD19+","marker_2":"CD138-","marker_3":"pVIII-peptide+","marker_4":null,"marker_5":null,"marker_6":null},{"subject_code":"SI Phage M6","subject_id":24,"subject_gender":null,"subject_species":"Mouse","subject_ethnicity":"","project_id":1,"project_name":"Mouse single immunization","project_parent_id":-1,"sra_accession":null,"lab_id":1,"lab_name":"Jamie Scott Lab","disease_state_id":null,"disease_state_name":null,"case_control_id":1,"case_control_name":"Case","sample_id":99,"project_sample_id":99,"sequence_count":50000,"project_sample_note":"Single immunization with pure phage","sra_run_id":null,"sample_name":"1-SPPB-9-6","subject_age":null,"sample_subject_id":24,"dna_id":1,"dna_type":"cDNA","sample_source_id":3,"sample_source_name":"Spleen","lab_cell_subset_name":"Plasma Cell","ireceptor_cell_subset_name":"Plasma Cell","marker_1":"CD138+","marker_2":null,"marker_3":null,"marker_4":null,"marker_5":null,"marker_6":null},{"subject_code":"SI Phage M7","subject_id":25,"subject_gender":null,"subject_species":"Mouse","subject_ethnicity":"","project_id":1,"project_name":"Mouse single immunization","project_parent_id":-1,"sra_accession":null,"lab_id":1,"lab_name":"Jamie Scott Lab","disease_state_id":null,"disease_state_name":null,"case_control_id":1,"case_control_name":"Case","sample_id":12,"project_sample_id":12,"sequence_count":153250,"project_sample_note":"Single immunization with pure phage","sra_run_id":null,"sample_name":"1-AS-M7","subject_age":null,"sample_subject_id":25,"dna_id":1,"dna_type":"cDNA","sample_source_id":3,"sample_source_name":"Spleen","lab_cell_subset_name":"Antigen Specific B Cell","ireceptor_cell_subset_name":"Mature B Cell","marker_1":"CD19+","marker_2":"CD138-","marker_3":"pVIII-peptide+","marker_4":null,"marker_5":null,"marker_6":null},{"subject_code":"SI Phage M7","subject_id":25,"subject_gender":null,"subject_species":"Mouse","subject_ethnicity":"","project_id":1,"project_name":"Mouse single immunization","project_parent_id":-1,"sra_accession":null,"lab_id":1,"lab_name":"Jamie Scott Lab","disease_state_id":null,"disease_state_name":null,"case_control_id":1,"case_control_name":"Case","sample_id":26,"project_sample_id":26,"sequence_count":216570,"project_sample_note":"Single immunization with pure phage","sra_run_id":null,"sample_name":"1-PC-M7","subject_age":null,"sample_subject_id":25,"dna_id":1,"dna_type":"cDNA","sample_source_id":3,"sample_source_name":"Spleen","lab_cell_subset_name":"Plasma Cell","ireceptor_cell_subset_name":"Plasma Cell","marker_1":"CD138+","marker_2":null,"marker_3":null,"marker_4":null,"marker_5":null,"marker_6":null},{"subject_code":"SI Phage M7","subject_id":25,"subject_gender":null,"subject_species":"Mouse","subject_ethnicity":"","project_id":1,"project_name":"Mouse single immunization","project_parent_id":-1,"sra_accession":null,"lab_id":1,"lab_name":"Jamie Scott Lab","disease_state_id":null,"disease_state_name":null,"case_control_id":1,"case_control_name":"Case","sample_id":86,"project_sample_id":86,"sequence_count":42207,"project_sample_note":"Single immunization with pure phage","sra_run_id":null,"sample_name":"1-SPAg-9-7","subject_age":null,"sample_subject_id":25,"dna_id":1,"dna_type":"cDNA","sample_source_id":3,"sample_source_name":"Spleen","lab_cell_subset_name":"Antigen Specific B Cell","ireceptor_cell_subset_name":"Mature B Cell","marker_1":"CD19+","marker_2":"CD138-","marker_3":"pVIII-peptide+","marker_4":null,"marker_5":null,"marker_6":null},{"subject_code":"SI Phage M7","subject_id":25,"subject_gender":null,"subject_species":"Mouse","subject_ethnicity":"","project_id":1,"project_name":"Mouse single immunization","project_parent_id":-1,"sra_accession":null,"lab_id":1,"lab_name":"Jamie Scott Lab","disease_state_id":null,"disease_state_name":null,"case_control_id":1,"case_control_name":"Case","sample_id":100,"project_sample_id":100,"sequence_count":115238,"project_sample_note":"Single immunization with pure phage","sra_run_id":null,"sample_name":"1-SPPB-9-7","subject_age":null,"sample_subject_id":25,"dna_id":1,"dna_type":"cDNA","sample_source_id":3,"sample_source_name":"Spleen","lab_cell_subset_name":"Plasma Cell","ireceptor_cell_subset_name":"Plasma Cell","marker_1":"CD138+","marker_2":null,"marker_3":null,"marker_4":null,"marker_5":null,"marker_6":null},{"subject_code":"SI Phage M8","subject_id":26,"subject_gender":null,"subject_species":"Mouse","subject_ethnicity":"","project_id":1,"project_name":"Mouse single immunization","project_parent_id":-1,"sra_accession":null,"lab_id":1,"lab_name":"Jamie Scott Lab","disease_state_id":null,"disease_state_name":null,"case_control_id":1,"case_control_name":"Case","sample_id":13,"project_sample_id":13,"sequence_count":112942,"project_sample_note":"Single immunization with pure phage","sra_run_id":null,"sample_name":"1-AS-M8","subject_age":null,"sample_subject_id":26,"dna_id":1,"dna_type":"cDNA","sample_source_id":3,"sample_source_name":"Spleen","lab_cell_subset_name":"Antigen Specific B Cell","ireceptor_cell_subset_name":"Mature B Cell","marker_1":"CD19+","marker_2":"CD138-","marker_3":"pVIII-peptide+","marker_4":null,"marker_5":null,"marker_6":null},{"subject_code":"SI Phage M8","subject_id":26,"subject_gender":null,"subject_species":"Mouse","subject_ethnicity":"","project_id":1,"project_name":"Mouse single immunization","project_parent_id":-1,"sra_accession":null,"lab_id":1,"lab_name":"Jamie Scott Lab","disease_state_id":null,"disease_state_name":null,"case_control_id":1,"case_control_name":"Case","sample_id":87,"project_sample_id":87,"sequence_count":15092,"project_sample_note":"Single immunization with pure phage","sra_run_id":null,"sample_name":"1-SPAg-9-8","subject_age":null,"sample_subject_id":26,"dna_id":1,"dna_type":"cDNA","sample_source_id":3,"sample_source_name":"Spleen","lab_cell_subset_name":"Antigen Specific B Cell","ireceptor_cell_subset_name":"Mature B Cell","marker_1":"CD19+","marker_2":"CD138-","marker_3":"pVIII-peptide+","marker_4":null,"marker_5":null,"marker_6":null},{"subject_code":"Pooled mice SI Phage 2d","subject_id":30,"subject_gender":null,"subject_species":"Mouse","subject_ethnicity":"","project_id":1,"project_name":"Mouse single immunization","project_parent_id":-1,"sra_accession":null,"lab_id":1,"lab_name":"Jamie Scott Lab","disease_state_id":null,"disease_state_name":null,"case_control_id":1,"case_control_name":"Case","sample_id":14,"project_sample_id":14,"sequence_count":139578,"project_sample_note":"Single immunization with pure phage","sra_run_id":null,"sample_name":"1-PC-B2","subject_age":null,"sample_subject_id":30,"dna_id":1,"dna_type":"cDNA","sample_source_id":2,"sample_source_name":"Blood","lab_cell_subset_name":"Plasma Cell","ireceptor_cell_subset_name":"Plasma Cell","marker_1":"CD138+","marker_2":null,"marker_3":null,"marker_4":null,"marker_5":null,"marker_6":null},{"subject_code":"Pooled mice SI Phage 2d","subject_id":30,"subject_gender":null,"subject_species":"Mouse","subject_ethnicity":"","project_id":1,"project_name":"Mouse single immunization","project_parent_id":-1,"sra_accession":null,"lab_id":1,"lab_name":"Jamie Scott Lab","disease_state_id":null,"disease_state_name":null,"case_control_id":1,"case_control_name":"Case","sample_id":88,"project_sample_id":88,"sequence_count":47478,"project_sample_note":"Single immunization with pure phage","sra_run_id":null,"sample_name":"1-BPB-2","subject_age":null,"sample_subject_id":30,"dna_id":1,"dna_type":"cDNA","sample_source_id":2,"sample_source_name":"Blood","lab_cell_subset_name":"Plasma Cell","ireceptor_cell_subset_name":"Plasma Cell","marker_1":"CD138+","marker_2":null,"marker_3":null,"marker_4":null,"marker_5":null,"marker_6":null},{"subject_code":"Pooled mice SI Phage 4d","subject_id":31,"subject_gender":null,"subject_species":"Mouse","subject_ethnicity":"","project_id":1,"project_name":"Mouse single immunization","project_parent_id":-1,"sra_accession":null,"lab_id":1,"lab_name":"Jamie Scott Lab","disease_state_id":null,"disease_state_name":null,"case_control_id":1,"case_control_name":"Case","sample_id":15,"project_sample_id":15,"sequence_count":143578,"project_sample_note":"Single immunization with pure phage","sra_run_id":null,"sample_name":"1-PC-B4","subject_age":null,"sample_subject_id":31,"dna_id":1,"dna_type":"cDNA","sample_source_id":2,"sample_source_name":"Blood","lab_cell_subset_name":"Plasma Cell","ireceptor_cell_subset_name":"Plasma Cell","marker_1":"CD138+","marker_2":null,"marker_3":null,"marker_4":null,"marker_5":null,"marker_6":null},{"subject_code":"Pooled mice SI Phage 4d","subject_id":31,"subject_gender":null,"subject_species":"Mouse","subject_ethnicity":"","project_id":1,"project_name":"Mouse single immunization","project_parent_id":-1,"sra_accession":null,"lab_id":1,"lab_name":"Jamie Scott Lab","disease_state_id":null,"disease_state_name":null,"case_control_id":1,"case_control_name":"Case","sample_id":89,"project_sample_id":89,"sequence_count":57548,"project_sample_note":"Single immunization with pure phage","sra_run_id":null,"sample_name":"1-BPB-4","subject_age":null,"sample_subject_id":31,"dna_id":1,"dna_type":"cDNA","sample_source_id":2,"sample_source_name":"Blood","lab_cell_subset_name":"Plasma Cell","ireceptor_cell_subset_name":"Plasma Cell","marker_1":"CD138+","marker_2":null,"marker_3":null,"marker_4":null,"marker_5":null,"marker_6":null},{"subject_code":"SI Phage M19","subject_id":27,"subject_gender":null,"subject_species":"Mouse","subject_ethnicity":"","project_id":1,"project_name":"Mouse single immunization","project_parent_id":-1,"sra_accession":null,"lab_id":1,"lab_name":"Jamie Scott Lab","disease_state_id":null,"disease_state_name":null,"case_control_id":1,"case_control_name":"Case","sample_id":19,"project_sample_id":19,"sequence_count":120068,"project_sample_note":"Single immunization with pure phage","sra_run_id":null,"sample_name":"1-PC-M19","subject_age":null,"sample_subject_id":27,"dna_id":1,"dna_type":"cDNA","sample_source_id":3,"sample_source_name":"Spleen","lab_cell_subset_name":"Plasma Cell","ireceptor_cell_subset_name":"Plasma Cell","marker_1":"CD138+","marker_2":null,"marker_3":null,"marker_4":null,"marker_5":null,"marker_6":null},{"subject_code":"SI Phage M19","subject_id":27,"subject_gender":null,"subject_species":"Mouse","subject_ethnicity":"","project_id":1,"project_name":"Mouse single immunization","project_parent_id":-1,"sra_accession":null,"lab_id":1,"lab_name":"Jamie Scott Lab","disease_state_id":null,"disease_state_name":null,"case_control_id":1,"case_control_name":"Case","sample_id":93,"project_sample_id":93,"sequence_count":107776,"project_sample_note":"Single immunization with pure phage","sra_run_id":null,"sample_name":"1-SPPB-2-19","subject_age":null,"sample_subject_id":27,"dna_id":1,"dna_type":"cDNA","sample_source_id":3,"sample_source_name":"Spleen","lab_cell_subset_name":"Plasma Cell","ireceptor_cell_subset_name":"Plasma Cell","marker_1":"CD138+","marker_2":null,"marker_3":null,"marker_4":null,"marker_5":null,"marker_6":null},{"subject_code":"SI Phage M20","subject_id":28,"subject_gender":null,"subject_species":"Mouse","subject_ethnicity":"","project_id":1,"project_name":"Mouse single immunization","project_parent_id":-1,"sra_accession":null,"lab_id":1,"lab_name":"Jamie Scott Lab","disease_state_id":null,"disease_state_name":null,"case_control_id":1,"case_control_name":"Case","sample_id":20,"project_sample_id":20,"sequence_count":216638,"project_sample_note":"Single immunization with pure phage","sra_run_id":null,"sample_name":"1-PC-M20","subject_age":null,"sample_subject_id":28,"dna_id":1,"dna_type":"cDNA","sample_source_id":3,"sample_source_name":"Spleen","lab_cell_subset_name":"Plasma Cell","ireceptor_cell_subset_name":"Plasma Cell","marker_1":"CD138+","marker_2":null,"marker_3":null,"marker_4":null,"marker_5":null,"marker_6":null},
{"subject_code":"IAVI 84","subject_id":1,"subject_gender":null,"subject_ethnicity":"NA","project_id":1,"project_name":"Mining the antibodyome for HIV-1 neutralizing antibodies with next generation sequencing and phylogenetic pairing of heavy\/light chains","project_parent_id":-1,"sra_accession":"SRP018335","lab_id":1,"lab_name":"National Institute of Allergy and Infectious Disease-Vaccine Research Center: Kwong Lab","disease_state_id":7,"disease_state_name":"HIV-1 infected for at least 3 years, not receiving antiretroviral treatment","case_control_id":1,"case_control_name":"Case","sample_id":1,"project_sample_id":1,"sequence_count":253684,"project_sample_note":null,"sra_run_id":"SRR654169","sample_name":"SRS388604","subject_age":null,"sample_subject_id":1,"dna_id":1,"dna_type":"cDNA","sample_source_id":1,"sample_source_name":"PBMCs","lab_cell_subset_name":"B cell memory","ireceptor_cell_subset_name":"Memory B Cell","marker_1":"IgG+","marker_2":" CD19+","marker_3":" sIgG+","marker_4":" negative depletion to CD3","marker_5":" CD14","marker_6":" CD16, IgM, IgA, IgD ","sequences":167793},
{"subject_code":"IAVI 84","subject_id":1,"subject_gender":null,"subject_ethnicity":"NA","project_id":1,"project_name":"Mining the antibodyome for HIV-1 neutralizing antibodies with next generation sequencing and phylogenetic pairing of heavy\/light chains","project_parent_id":-1,"sra_accession":"SRP018335","lab_id":1,"lab_name":"National Institute of Allergy and Infectious Disease-Vaccine Research Center: Kwong Lab","disease_state_id":7,"disease_state_name":"HIV-1 infected for at least 3 years, not receiving antiretroviral treatment","case_control_id":1,"case_control_name":"Case","sample_id":1,"project_sample_id":2,"sequence_count":873981,"project_sample_note":null,"sra_run_id":"SRR654170","sample_name":"SRS388604","subject_age":null,"sample_subject_id":1,"dna_id":1,"dna_type":"cDNA","sample_source_id":1,"sample_source_name":"PBMCs","lab_cell_subset_name":"B cell memory","ireceptor_cell_subset_name":"Memory B Cell","marker_1":"IgG+","marker_2":" CD19+","marker_3":" sIgG+","marker_4":" negative depletion to CD3","marker_5":" CD14","marker_6":" CD16, IgM, IgA, IgD ","sequences":562633},
{"subject_code":"N152","subject_id":2,"subject_gender":null,"subject_ethnicity":"NA","project_id":1,"project_name":"Mining the antibodyome for HIV-1 neutralizing antibodies with next generation sequencing and phylogenetic pairing of heavy\/light chains","project_parent_id":-1,"sra_accession":"SRP018335","lab_id":1,"lab_name":"National Institute of Allergy and Infectious Disease-Vaccine Research Center: Kwong Lab","disease_state_id":8,"disease_state_name":"HIV-1 infected for 20 years off antiretroviral treament","case_control_id":1,"case_control_name":"Case","sample_id":2,"project_sample_id":4,"sequence_count":755431,"project_sample_note":null,"sra_run_id":"SRR654171","sample_name":"SRS1415051","subject_age":null,"sample_subject_id":2,"dna_id":1,"dna_type":"cDNA","sample_source_id":1,"sample_source_name":"PBMCs","lab_cell_subset_name":"B cell memory","ireceptor_cell_subset_name":"Memory B Cell","marker_1":"CD19+ IgM-","marker_2":" IgD-","marker_3":" IgA-","marker_4":"","marker_5":"","marker_6":"","sequences":233596},
{"subject_code":"BC0014A","subject_id":3,"subject_gender":null,"subject_ethnicity":"NA","project_id":2,"project_name":"\"The Different T-cell Receptor Repertoires in Breast Cancer Tumors, Draining Lymph Nodes, and Adjacent Tissues\"","project_parent_id":-1,"sra_accession":"SRP083115","lab_id":2,"lab_name":"\"Xijing Hospital, Fourth Military Medical University\": Department of Vascular and Endocrine Surgery","disease_state_id":9,"disease_state_name":"BREAST CANCER (ER3+,PR3+, LN ratio 0\/23, luminal B tumor subtype, 0.2% tumor infiltration, 2%nontumor infiltration, Ki67 28%)","case_control_id":1,"case_control_name":"Case","sample_id":3,"project_sample_id":7,"sequence_count":2876698,"project_sample_note":null,"sra_run_id":"SRR4102110","sample_name":"SRS1660132","subject_age":null,"sample_subject_id":3,"dna_id":2,"dna_type":"gDNA","sample_source_id":2,"sample_source_name":"Lymph node tissue","lab_cell_subset_name":"CD4\/CD8 T cell","ireceptor_cell_subset_name":"Mature T Cell","marker_1":"CD3+ CD4+ CD8+","marker_2":"","marker_3":"","marker_4":"","marker_5":"","marker_6":"","sequences":2876698}
];

/**********************************************************
* Functions
**********************************************************/

function showData(json) {
	// Initial variables. These should be provided by the gateway, but they are constants for now.
	// It is possible to change the data set being used by changing the source data and the 
	// sequenceAPIData variable as desired.
	//
	// sequenceAPIData - Whether or not the data came from the sequence_summary API or not.
	//
	// ireceptorData - The data from the gateway in either the /v2/sequence_summary format
	// or the /v1/samples format.
	var ireceptorData;
	var sequenceAPIData;

	// Example data from /v2/sequence_summary
	//ireceptorData = v2SequencesSummaryIPA;
	//sequenceAPIData = true;

	// Example data from /v1/samples
	// ireceptorData = v1SamplesSummaryCombined;
	ireceptorData = json;
	sequenceAPIData = false;
	
// console.log(json);

	// Aggregate over the projects and get the number of projects.
    aggregateData = irAggregateData("project_name", ireceptorData, sequenceAPIData);
	var numProjects = aggregateData.length;

// console.log(aggregateData);

	
	// Get the total sequence count by looping over the sequence counts for
	// all of the projects.
	var numSequences = 0;
	for (project in aggregateData)
	{
		numSequences += aggregateData[project].count;
	}
	
	// Aggregate over the subjects and get the number of subjects. We don't use the
	// aggregated data.
	aggregateData = irAggregateData("subject_code", ireceptorData, sequenceAPIData);
	var numSubjects = aggregateData.length;
	
	// Aggregate over the samples and get the numebr of samples. We don't use the 
	// aggregated data.
    aggregateData = irAggregateData("sample_name", ireceptorData, sequenceAPIData);
	var numSamples = aggregateData.length;	
	
	/* Old code to put counts in three columns of the table.
	document.getElementById("labs").innerHTML = "<center><h1>" + numProjects + " Projects</h1></center>";
	document.getElementById("subjects").innerHTML = "<center><h1>" + numSubjects + " Subjects</h1></center>";
	document.getElementById("samples").innerHTML = "<center><h1>" + numSamples + " Samples</h1></center>";
	*/
	
	// Generate the text content for displaying the summary data.
	document.getElementById("header").innerHTML = "Your query returned " + numSequences + " Sequences from " +
	    numProjects + " Projects, " + 
	    numSubjects + " Subjects, and " + 
	    numSamples + " Samples!";

    // Generate the six charts for the six types of aggregated data. For each chart, we
	// get the aggregated data for the field of interest, convert that aggregated data
	// into a data structure that is appropriate for HighChart to make a chart out of,
	// and then finally render the chart (using HighChart) in the HTML container of 
	// choice.
	var chart1;
    aggregateData = irAggregateData("ireceptor_cell_subset_name", ireceptorData, sequenceAPIData);
	chart1 = irBuildChart("Cell Type", aggregateData, "pie");
    Highcharts.chart('container1', chart1);
	
	var chart2;
    aggregateData = irAggregateData("subject_species", ireceptorData, sequenceAPIData);
	chart2 = irBuildChart("Species", aggregateData, "pie");
    Highcharts.chart('container2', chart2);
	
	var chart3;
    aggregateData = irAggregateData("sample_source_name", ireceptorData, sequenceAPIData);
	chart3 = irBuildChart("Tissue Source", aggregateData, "pie");
    Highcharts.chart('container3', chart3);
	
    var chart4;
    aggregateData = irAggregateData("project_type", ireceptorData, sequenceAPIData);
	chart4 = irBuildChart("Study Type", aggregateData, "pie");
    Highcharts.chart('container4', chart4);
	
	var chart5;
    aggregateData = irAggregateData("disease_state_name", ireceptorData, sequenceAPIData);
	chart5 = irBuildChart("Disease State", aggregateData, "pie");
    Highcharts.chart('container5', chart5);
	
	var chart6;
    aggregateData = irAggregateData("dna_type", ireceptorData, sequenceAPIData);
	chart6 = irBuildChart("DNA Type", aggregateData, "pie");
    Highcharts.chart('container6', chart6);	
}


// Build a chart for the iReceptor aggregation data using HighCharts.
function irBuildChart(fieldTitle, data, type)
{
	// Build a chart using the "HighCharts" chart, using the data provided.
	var debugLevel = 0;
    var seriesData = [];
	var count = 0;
	// Convert iReceptor aggregate data into a form for HighChart.
	for (d in data)
	{
		 if (debugLevel > 0)
			 document.getElementById("debug").innerHTML += "--" + data[d] + "--" + "<br>";
		 if (debugLevel > 0)
			 document.getElementById("debug").innerHTML += "--" + data[d].name + " = " + data[d].count + "--" + "<br>";
		 seriesData[count] = {name:data[d].name,y:data[d].count};
	     count = count + 1;
	}
	
	// Generate the chart data structure for HighChart.
	var chartData;
    chartData = {
        chart: {
            plotBackgroundColor: null,
            plotBorderWidth: null,
            plotShadow: false,
            type: type
        },
        title: {
            text: fieldTitle
        },
        tooltip: {
            pointFormat: '<b>{point.y:.0f} ({point.percentage:.1f}%)</b>'
        },
        plotOptions: {
            pie: {
                allowPointSelect: true,
                cursor: 'pointer',
                dataLabels: {
                    enabled: false
                },
                showInLegend: true
            }
        },
        series: [{
            name: fieldTitle,
            colorByPoint: true,
            data: seriesData
        }]
    };
    return chartData;
}


// Do an aggregation count across the JSON data, aggregating on the series
// name provided in "seriesName" and aggregating the counts in "countField".
//
// seriesName: String that represents the series of interest that we are aggregating on (e.g. subject_species).
//
// jsonData: This is JSON data from the iReceptor API, in either the format 
// provided by the /v2/sequence_summary API call or the /v1 and /v2 samples API call.
// 
// aggregationSummary: A boolean flag that denotes whether jsonData came from
// the /v2/sequence_summary API or not. If not, we assume the data came from the
// /v1/samples API.
function irAggregateData(seriesName, jsonData, sequenceSummaryAPI)
{
	// Debug level so we can debug the code...
	var debugLevel = 0;
	// Arrays to hold the aggregated value names and aggregated counts
	// e.g. an aggregateName might be "Mature T Cell" and the count might be 1,000,000 sequences
	var aggregateName = [];
	var aggregateCount = [];
	var aggregationData;
    var countField;
	
	// Debug: tell us the series name we are looking for.
	if (debugLevel > 0)
	    document.getElementById("debug").innerHTML += seriesName + "<br>";
	
	if (sequenceSummaryAPI)
	{
		aggregationList = jsonData.aggregation_summary;
		countField = "sequences";
	}
	else
	{
		aggregationList = jsonData;
		countField = "sequence_count";
	}
	if (debugLevel > 0)
	    document.getElementById("debug").innerHTML += "Hello" + "<br>";
	if (debugLevel > 0)
	    document.getElementById("debug").innerHTML += "aggregation list has " + aggregationList.stringify + "<br>";
	
	// Process each element in the data from iReceptor. 
    var count = 0
	for (element in aggregationList)
    {
		// Get the element.
		if (sequenceSummaryAPI)
		{
	        if (debugLevel > 0)
	            document.getElementById("debug").innerHTML += element + "<br>";
			elementData = aggregationList[element];
	        if (debugLevel > 0)
	           document.getElementById("debug").innerHTML += elementData + "<br>";			
		}
		else
		{
		    elementData = aggregationList[count];
		}
		
		// Get the value of the field we are aggregating on for this element.
		var fieldValue;
		var fieldCount;
	    fieldValue = elementData[seriesName];

        if (fieldValue == null) 
		{
		    // If it doesn't exist in this element, then keep track of the count 
		    // of the data that doesn't have this field. This should be rare, but
		    // it can happen if the data models are different and are missing data.
		    fieldValue = "NODATA";
		    fieldCount = elementData[countField];
		}
		else
		{
			// If the element is found, extract the count.
		    fieldCount = elementData[countField];
		}
		// If the field value is long, truncate it for esthetic purposes.
		// Note: This should probably happen in the chart building function,
		// as this is an aggregator function and probably shouldn't change
		// the data.
		fieldValue = fieldValue.substr(0,16);

		// Do the aggregation step.
		if (aggregateName[fieldValue] == null)
		{
			// If we haven't seen this field before (it doesn't exist in our 
			// aggregator data structure) then initialize the cound for this
			// field.
		    aggregateCount[fieldValue] = fieldCount;
			aggregateName[fieldValue] = fieldValue;
		}
		else
		{
			// If we have seen this field before, increment the count.
		    aggregateCount[fieldValue] += fieldCount;
		}

		// Do some debug output if required.
		if (debugLevel > 1)
		{
            var jsonString1 = JSON.stringify(fieldValue);
	        var jsonString2 = JSON.stringify(fieldCount);
		    document.getElementById("debug").innerHTML += "--" + aggregateName[fieldValue] + " = " + aggregateCount[fieldValue] + "--" + "<br>";
            document.getElementById("debug").innerHTML += jsonString1 + " " + jsonString2 + "<br>";
		}
	    count = count + 1;
    }
	
	// Once we have the fully aggregated data, iterate over the unique
	// aggregate elements and generate the series data with a name and
	// value pair
	count = 0;
    var seriesData = [];
	for (element in aggregateCount)
	{
		 if (debugLevel > 0)
			 document.getElementById("debug").innerHTML += "**" + element + " = " + aggregateCount[element] + "**" + "<br>";
		 seriesData[count] = {name:element,count:aggregateCount[element]};
	     count = count + 1;
	}
	if (debugLevel > 0)
	    document.getElementById("debug").innerHTML += "<br>";
	
	// Return the aggregate name/value list.
    return seriesData;
}