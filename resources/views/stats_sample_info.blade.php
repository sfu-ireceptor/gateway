<div class="row">
	<div class="col-md-3">
		<h4>Study</h4>
		<p>
			<strong>@lang('short.study_title'):</strong>
			<span class="study_title">{{ $sample->study_title }}</span>
		</p>
		<p>
			<strong>@lang('short.study_group_description'):</strong>
			<span class="study_group_description">{{ $sample->study_group_description }}</span>
		</p>										
	</div>
	<div class="col-md-3">
		<h4>Subject</h4>
		<p>
			<strong>@lang('short.subject_id'):</strong>
			<span class="subject_id">{{ $sample->subject_id }}</span>
		</p>
		<p>
			<strong>@lang('short.disease_diagnosis'):</strong>
			<span class="disease_diagnosis">{{ $sample->disease_diagnosis }}</span>
		</p>										
		<p>
			<strong>Age:</strong>
			<span class="ir_subject_age_min">{{ $sample->ir_subject_age_min }}</span>
			-
			<span class="ir_subject_age_max">{{ $sample->ir_subject_age_max }}</span>
			(<span class="age_unit">{{ $sample->age_unit }}</span>)
		</p>
		<p>
			<strong>@lang('short.sex'):</strong>
			<span class="sex">{{ $sample->sex }}</span>
		</p>
	</div>
	<div class="col-md-3">
		<h4>Sample</h4>
		<p>
			<strong>@lang('short.sample_id'):</strong>
			<span class="sample_id">{{ $sample->sample_id }}</span>
		</p>
		<p>
			<strong>@lang('short.cell_subset'):</strong>
			<span class="cell_subset">{{ $sample->cell_subset }}></span>
		</p>
		<p>
			<strong>@lang('short.pcr_target_locus'):</strong>
			<span class="pcr_target_locus">{{ $sample->pcr_target_locus }}</span>
		</p>
	</div>
	<div class="col-md-3">
		<h4>Sequences</h4>
		<p>
			<strong>Rearrangement Count:</strong>
			<span class="ir_sequence_count">{{ $sample->ir_sequence_count }}</span>
		</p>
	</div>
</div>
