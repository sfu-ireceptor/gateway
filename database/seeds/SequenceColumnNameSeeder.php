<?php

use Illuminate\Database\Seeder;

use App\SequenceColumnName;

class SequenceColumnNameSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
	  	DB::table('sequence_column_name')->truncate();

	    $l = [
			[
			  "name" => "id",
			  "title" => "id",
			  "enabled" => true,
			],
			[
			  "name" => "seq_id",
			  "title" => "seq_id",
			  "enabled" => true,
			],
			[
			  "name" => "seq_name",
			  "title" => "Sequence Tag",
			  "enabled" => true,
			],
			[
			  "name" => "project_sample_id",
			  "title" => "project_sample_id",
			  "enabled" => true,
			],
			[
			  "name" => "sequence_id",
			  "title" => "sequence_id",
			  "enabled" => true,
			],
			[
			  "name" => "vgene_string",
			  "title" => "V-Gene",
			  "enabled" => true,
			],
			[
			  "name" => "vgene_family",
			  "title" => "V-Gene Family",
			  "enabled" => true,
			],
			[
			  "name" => "vgene_gene",
			  "title" => "V-Gene Gene",
			  "enabled" => true,
			],
			[
			  "name" => "vgene_allele",
			  "title" => "V-Gene Allele",
			  "enabled" => true,
			],
			[
			  "name" => "jgene_string",
			  "title" => "J-Gene",
			  "enabled" => true,
			],
			[
			  "name" => "jgene_family",
			  "title" => "J-Gene Family",
			  "enabled" => true,
			],
			[
			  "name" => "jgene_gene",
			  "title" => "J-Gene Gene",
			  "enabled" => true,
			],
			[
			  "name" => "jgene_allele",
			  "title" => "J-Gene Allele",
			  "enabled" => true,
			],
			[
			  "name" => "dgene_string",
			  "title" => "D-Gene String",
			  "enabled" => true,
			],
			[
			  "name" => "dgene_family",
			  "title" => "D-Gene Family",
			  "enabled" => true,
			],
			[
			  "name" => "dgene_gene",
			  "title" => "D-Gene Gene",
			  "enabled" => true,
			],
			[
			  "name" => "dgene_allele",
			  "title" => "D-Gene Allele",
			  "enabled" => true,
			],
			[
			  "name" => "functionality",
			  "title" => "Functionality",
			  "enabled" => true,
			],
			[
			  "name" => "functionality_comment",
			  "title" => "Functionality Comment",
			  "enabled" => true,
			],
			[
			  "name" => "orientation",
			  "title" => "Orientation",
			  "enabled" => true,
			],
			[
			  "name" => "vgene_score",
			  "title" => "V-Gene Score",
			  "enabled" => true,
			],
			[
			  "name" => "vgene_probability",
			  "title" => "V-Gene Probability",
			  "enabled" => true,
			],
			[
			  "name" => "dregion_reading_frame",
			  "title" => "D Region Reading Frame",
			  "enabled" => true,
			],
			[
			  "name" => "cdr1_length",
			  "title" => "CDR1 Length",
			  "enabled" => false,
			],
			[
			  "name" => "cdr2_length",
			  "title" => "CDR2 Length",
			  "enabled" => false,
			],
			[
			  "name" => "cdr3_length",
			  "title" => "CDR3 Length",
			  "enabled" => true,
			],
			[
			  "name" => "vdjregion_sequence_nt",
			  "title" => "VDJ Region NT sequence",
			  "enabled" => false,
			],
			[
			  "name" => "vjregion_sequence_nt",
			  "title" => "VJ Region NT Sequence",
			  "enabled" => false,
			],
			[
			  "name" => "djregion_sequence_nt",
			  "title" => "DJ Region NT Sequence",
			  "enabled" => false,
			],
			[
			  "name" => "vregion_sequence_nt",
			  "title" => "V Region NT Sequence",
			  "enabled" => true,
			],
			[
			  "name" => "jregion_sequence_nt",
			  "title" => "J Region NT Sequence",
			  "enabled" => true,
			],
			[
			  "name" => "dregion_sequence_nt",
			  "title" => "D Region NT Sequence",
			  "enabled" => true,
			],
			[
			  "name" => "fr1region_sequence_nt",
			  "title" => "FR1 Region NT Sequence",
			  "enabled" => false,
			],
			[
			  "name" => "fr2region_sequence_nt",
			  "title" => "FR2 Region NT Sequence",
			  "enabled" => false,
			],
			[
			  "name" => "fr3region_sequence_nt",
			  "title" => "FR3 Region NT Sequence",
			  "enabled" => false,
			],
			[
			  "name" => "fr4region_sequence_nt",
			  "title" => "FR4 Region NT Sequence",
			  "enabled" => false,
			],
			[
			  "name" => "cdr1region_sequence_nt",
			  "title" => "CDR1 Region NT Sequence",
			  "enabled" => false,
			],
			[
			  "name" => "cdr2region_sequence_nt",
			  "title" => "CDR2 Region NT Sequence",
			  "enabled" => false,
			],
			[
			  "name" => "cdr3region_sequence_nt",
			  "title" => "CDR3 Region Sequence",
			  "enabled" => true,
			],
			[
			  "name" => "junction_sequence_nt",
			  "title" => "Junction Sequence",
			  "enabled" => true,
			],
			[
			  "name" => "vdjregion_sequence_nt_gapped",
			  "title" => "VDJ Region Gapped NT Sequence",
			  "enabled" => false,
			],
			[
			  "name" => "vjregion_sequence_nt_gapped",
			  "title" => "VJ Region Gapped NT Sequence",
			  "enabled" => false,
			],
			[
			  "name" => "vregion_sequence_nt_gapped",
			  "title" => "V Region Gapped NT Sequence",
			  "enabled" => false,
			],
			[
			  "name" => "jregion_sequence_nt_gapped",
			  "title" => "Jregion Gapped NT Sequence",
			  "enabled" => false,
			],
			[
			  "name" => "dregion_sequence_nt_gapped",
			  "title" => "D Region Gapped NT Sequence",
			  "enabled" => false,
			],
			[
			  "name" => "fr1region_sequence_nt_gapped",
			  "title" => "FR1 Region Gapped NT Sequence",
			  "enabled" => false,
			],
			[
			  "name" => "fr2region_sequence_nt_gapped",
			  "title" => "FR2 Region Gapped NT Sequence",
			  "enabled" => false,
			],
			[
			  "name" => "fr3region_sequence_nt_gapped",
			  "title" => "FR3 Region Gapped NT Sequence",
			  "enabled" => false,
			],
			[
			  "name" => "fr4region_sequence_nt_gapped",
			  "title" => "FR4 Region Gapped NT Sequence",
			  "enabled" => false,
			],
			[
			  "name" => "cdr1region_sequence_nt_gapped",
			  "title" => "CDR1 Region Gapped NT Sequence",
			  "enabled" => false,
			],
			[
			  "name" => "cdr2region_sequence_nt_gapped",
			  "title" => "CDR2 Region Gapped NT Sequence",
			  "enabled" => false,
			],
			[
			  "name" => "cdr3region_sequence_nt_gapped",
			  "title" => "CDR3 Region Gapped NT Sequence",
			  "enabled" => false,
			],
			[
			  "name" => "junction_sequence_nt_gapped",
			  "title" => "Junction Gapped NT Sequence",
			  "enabled" => false,
			],
			[
			  "name" => "vdjregion_sequence_aa",
			  "title" => "VDJ Region AA Sequence",
			  "enabled" => true,
			],
			[
			  "name" => "vjregion_sequence_aa",
			  "title" => "VJ Region AA Sequence",
			  "enabled" => true,
			],
			[
			  "name" => "vregion_sequence_aa",
			  "title" => "V Region AA Sequence",
			  "enabled" => true,
			],
			[
			  "name" => "jregion_sequence_aa",
			  "title" => "J Region AA Sequence",
			  "enabled" => true,
			],
			[
			  "name" => "fr1region_sequence_aa",
			  "title" => "FR1 Region AA Sequence",
			  "enabled" => false,
			],
			[
			  "name" => "fr2region_sequence_aa",
			  "title" => "FR2 Region AA Sequence",
			  "enabled" => false,
			],
			[
			  "name" => "fr3region_sequence_aa",
			  "title" => "FR3 Region AA Sequence",
			  "enabled" => false,
			],
			[
			  "name" => "fr4region_sequence_aa",
			  "title" => "FR4 Region AA Sequence",
			  "enabled" => false,
			],
			[
			  "name" => "cdr1region_sequence_aa",
			  "title" => "CDR1 Region AA Sequence",
			  "enabled" => false,
			],
			[
			  "name" => "cdr2region_sequence_aa",
			  "title" => "CDR2 Region AA Sequence",
			  "enabled" => false,
			],
			[
			  "name" => "cdr3region_sequence_aa",
			  "title" => "CDR3 Region AA Sequence",
			  "enabled" => true,
			],
			[
			  "name" => "junction_sequence_aa",
			  "title" => "Junction AA Sequence",
			  "enabled" => true,
			],
			[
			  "name" => "vdjregion_sequence_aa_gapped",
			  "title" => "VDJ Region Gapped AA Sequence",
			  "enabled" => false,
			],
			[
			  "name" => "vjregion_sequence_aa_gapped",
			  "title" => "VJ Region Gapped AA Sequence",
			  "enabled" => false,
			],
			[
			  "name" => "vregion_sequence_aa_gapped",
			  "title" => "V Region Gapped AA Sequence",
			  "enabled" => false,
			],
			[
			  "name" => "jregion_sequence_aa_gapped",
			  "title" => "J Region Gapped AA Sequence",
			  "enabled" => false,
			],
			[
			  "name" => "fr1region_sequence_aa_gapped",
			  "title" => "FR1 Region Gapped AA Sequence",
			  "enabled" => false,
			],
			[
			  "name" => "fr2region_sequence_aa_gapped",
			  "title" => "FR2 Region Gapped AA Sequence",
			  "enabled" => false,
			],
			[
			  "name" => "fr3region_sequence_aa_gapped",
			  "title" => "FR3 Region Gapped AA Sequence",
			  "enabled" => false,
			],
			[
			  "name" => "fr4region_sequence_aa_gapped",
			  "title" => "FR4 Region Gapped AA Sequence",
			  "enabled" => false,
			],
			[
			  "name" => "cdr1region_sequence_aa_gapped",
			  "title" => "CDR1 Region Gapped AA Sequence",
			  "enabled" => false,
			],
			[
			  "name" => "cdr2region_sequence_aa_gapped",
			  "title" => "CDR2 Region Gapped AA Sequence",
			  "enabled" => false,
			],
			[
			  "name" => "cdr3region_sequence_aa_gapped",
			  "title" => "CDR3 Region Gapped AA Sequence",
			  "enabled" => false,
			],
			[
			  "name" => "junction_sequence_aa_gapped",
			  "title" => "Junction Gapped AA Sequence",
			  "enabled" => false,
			],
			[
			  "name" => "vdjregion_start",
			  "title" => "VDJ Region Start Offset",
			  "enabled" => true,
			],
			[
			  "name" => "vdjregion_end",
			  "title" => "VDJ Region End Offset",
			  "enabled" => true,
			],
			[
			  "name" => "vjregion_start",
			  "title" => "VJ Region Start Offset",
			  "enabled" => true,
			],
			[
			  "name" => "vjregion_end",
			  "title" => "VJ Region End Offset",
			  "enabled" => true,
			],
			[
			  "name" => "djregion_start",
			  "title" => "DJ Region Start Offset",
			  "enabled" => true,
			],
			[
			  "name" => "djregion_end",
			  "title" => "DJ Region End Offset",
			  "enabled" => true,
			],
			[
			  "name" => "vregion_start",
			  "title" => "V Region Start Offset",
			  "enabled" => true,
			],
			[
			  "name" => "vregion_end",
			  "title" => "V Region End Offset",
			  "enabled" => true,
			],
			[
			  "name" => "jregion_start",
			  "title" => "J Region Start Offset",
			  "enabled" => true,
			],
			[
			  "name" => "jregion_end",
			  "title" => "J Region End Offset",
			  "enabled" => true,
			],
			[
			  "name" => "dregion_start",
			  "title" => "D Region Start Offset",
			  "enabled" => true,
			],
			[
			  "name" => "dregion_end",
			  "title" => "D Region End Offset",
			  "enabled" => true,
			],
			[
			  "name" => "fr1region_start",
			  "title" => "FR1 Region Start Offset",
			  "enabled" => false,
			],
			[
			  "name" => "fr1region_end",
			  "title" => "FR1 Region End Offset",
			  "enabled" => false,
			],
			[
			  "name" => "fr2region_start",
			  "title" => "FR2 Region Start Offset",
			  "enabled" => false,
			],
			[
			  "name" => "fr2region_end",
			  "title" => "FR2 Region End Offset",
			  "enabled" => false,
			],
			[
			  "name" => "fr3region_start",
			  "title" => "FR3 Region Start Offset",
			  "enabled" => false,
			],
			[
			  "name" => "fr3region_end",
			  "title" => "FR3 Region End Offset",
			  "enabled" => false,
			],
			[
			  "name" => "fr4region_start",
			  "title" => "FR4 Region Start Offset",
			  "enabled" => false,
			],
			[
			  "name" => "fr4region_end",
			  "title" => "FR4 Region End Offset",
			  "enabled" => false,
			],
			[
			  "name" => "cdr1region_start",
			  "title" => "CDR1 Region Start Offset",
			  "enabled" => false,
			],
			[
			  "name" => "cdr1region_end",
			  "title" => "CDR1 Region End Offset",
			  "enabled" => false,
			],
			[
			  "name" => "cdr2region_start",
			  "title" => "CDR2 Region Start Offset",
			  "enabled" => false,
			],
			[
			  "name" => "cdr2region_end",
			  "title" => "CDR2 Region End Offset",
			  "enabled" => false,
			],
			[
			  "name" => "cdr3region_start",
			  "title" => "CDR3 Region Start Offset",
			  "enabled" => true,
			],
			[
			  "name" => "cdr3region_end",
			  "title" => "CDR3 Region End Offset",
			  "enabled" => true,
			],
			[
			  "name" => "junction_start",
			  "title" => "Junction Start Offset",
			  "enabled" => true,
			],
			[
			  "name" => "junction_end",
			  "title" => "Junction End Offset",
			  "enabled" => true,
			],
			[
			  "name" => "vregion_mutation_string",
			  "title" => "V Region Mutation String",
			  "enabled" => true,
			],
			[
			  "name" => "fr1region_mutation_string",
			  "title" => "FR1 Region Mutation String",
			  "enabled" => true,
			],
			[
			  "name" => "fr2region_mutation_string",
			  "title" => "FR2 Region Mutation String",
			  "enabled" => true,
			],
			[
			  "name" => "fr3region_mutation_string",
			  "title" => "FR3 Region Mutation String",
			  "enabled" => true,
			],
			[
			  "name" => "cdr1region_mutation_string",
			  "title" => "CDR1 Region Mutation String",
			  "enabled" => true,
			],
			[
			  "name" => "cdr2region_mutation_string",
			  "title" => "CDR2 Region Mutation String",
			  "enabled" => true,
			],
			[
			  "name" => "cdr3region_mutation_string",
			  "title" => "CDR3 Region Mutation String",
			  "enabled" => true,
			],
			[
			  "name" => "annotation_tool",
			  "title" => "Annotation Tool",
			  "enabled" => true,
			]
	    ];
	  
	    foreach ($l as $item)
	    {
	      SequenceColumnName::create($item);
	    }
    }
}
