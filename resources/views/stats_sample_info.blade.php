<div class="row">
	<div class="col-md-3">
		<h4>Study</h4>
		<p>
			<strong>@lang('short.study_title'):</strong>
			@isset($sample->study_url)
				<a href="{{ $sample->study_url }}" target="_blank">
					{{ $sample->study_title }}
				</a>
			@else
				{{ $sample->study_title }}
			@endisset
		</p>
		<p>
			<strong>@lang('short.study_group_description'):</strong>
			{{ $sample->study_group_description }}
		</p>										
		<p>
			<strong>@lang('short.study_id'):</strong>
			@isset($sample->ncbi_url)
				<a href="{{ $sample->ncbi_url }}" title="{{ $sample->ncbi_url }}" target="_blank">
					{{ $sample->study_id }}
				</a>
			@else
				{{ $sample->study_id }}
			@endisset
		</p>
	</div>
	<div class="col-md-3">
		<h4>Subject</h4>
		<p>
			<strong>@lang('short.subject_id'):</strong>
			{{ $sample->subject_id }}
		</p>
		<p>
			<strong>@lang('short.disease_diagnosis'):</strong>
			{{ $sample->disease_diagnosis }}
		</p>										
		<p>
			<strong>Age:</strong>
			@if(isset($sample->ir_subject_age_min) && isset($sample->ir_subject_age_max) && isset($sample->age_unit) && ($sample->ir_subject_age_min == $sample->ir_subject_age_max))
				{{ $sample->ir_subject_age_min }}
				{{ str_plural($sample->age_unit, $sample->ir_subject_age_min) }}
			@elseif(isset($sample->age_unit))
				{{ $sample->ir_subject_age_min }}-{{ $sample->ir_subject_age_max }}
				{{ isset($sample->age_unit) ? str_plural($sample->age_unit, $sample->ir_subject_age_max) : ''}}
			@endif
		</p>
		<p>
			<strong>@lang('short.sex'):</strong>
			{{ $sample->sex }}
		</p>
	</div>
	<div class="col-md-3">
		<h4>Sample</h4>
		<p>
			<strong>@lang('short.sample_id'):</strong>
			{{ $sample->sample_id }}
		</p>
		<p>
			<strong>@lang('short.cell_subset'):</strong>
			{{ $sample->cell_subset }}
		</p>
		<p>
			<strong>@lang('short.pcr_target_locus'):</strong>
			{{ $sample->pcr_target_locus }}
		</p>
		<p>
			<strong>@lang('short.tissue'):</strong>
			{{ $sample->tissue }}
		</p>

	</div>
	<div class="col-md-3">
		<h4>Sequences</h4>
		<p>
			<strong>Rearrangement Count:</strong>
			{{ number_format($sample->ir_sequence_count) }}
		</p>
	</div>
</div>
