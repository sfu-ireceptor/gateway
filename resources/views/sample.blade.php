@extends('template')

@section('title', 'Search samples')

@section('content')

<div class="container-fluid sample_container">
	<div class="row">
		<div class="col-md-12">
			<ul class="nav nav-tabs nav-justified samples_sequences_nav">
				<li role="presentation" class="active"><a href="#"><b>Explore Samples</b></a></li>
				<li role="presentation"><a href="/sequences"><b> Explore Sequences</b></a></li>
			</ul>
		</div>
	</div>

	<div class="row">
		<div class="col-md-2">
		<div class="data_container_box">	
			{{ Form::open(array('url' => 'samples', 'role' => 'form', 'method' => 'get', 'class' => 'sample_search')) }}
				<input type="hidden" name="project_id_list" />
				<p>
					{{ Form::submit('Update Filters', array('class' => 'btn btn-primary search_samples')) }}
				</p>
			    <div class="form-group">
					{{ Form::label('subject_id', __('sp.subject_id')) }}
					{{ Form::text('subject_id', '', array('class' => 'form-control')) }}
				</div>

				<div class="form-group">
					{{ Form::label('sex', __('sp.sex')) }}
					{{ Form::select('sex', $subject_gender_list, '', array('class' => 'form-control')) }}
				</div>

			    <div class="form-group">
					{{ Form::label('ethnicity', __('sp.ethnicity')) }}
					{{ Form::select('ethnicity', $subject_ethnicity_list, '', array('class' => 'form-control')) }}
				</div>

				 <div class="form-group">
					{{ Form::label('subject_age_min', __('sp.age')) }}
					<div class="row">
						<div class="col-md-6">
							{{ Form::text('subject_age_min', '', array('class' => 'form-control', 'placeholder' => 'From')) }}
						</div>
						<div class="col-md-6">
							{{ Form::text('subject_age_max', '', array('class' => 'form-control', 'placeholder' => 'To')) }}
						</div>
					</div>
				</div>

			    <div class="form-group">
					{{ Form::label('sample_id', __('sp.sample_id')) }}
					{{ Form::text('sample_id', '', array('class' => 'form-control')) }}
				</div>

				<div class="form-group">
					{{ Form::label('cell_subset', __('sp.cell_subset')) }}
					@foreach ($cell_type_list as $id => $name)
					<div class="checkbox">
						<label>
						{{ Form::checkbox('cell_subset[]', $id) }}
						{{ $name }}
						</label>
					</div>
					@endforeach
				</div>

			    <div class="form-group">
					{{ Form::label('tissue', __('sp.tissue')) }}
					@foreach ($sample_source_list as $id => $name)
					<div class="checkbox">
						<label>
						{{ Form::checkbox('tissue[]', $id) }}
						{{ $name }}
						</label>
					</div>
					@endforeach
				</div>

				 <div class="form-group">
					{{ Form::label('library_source', __('sp.library_source')) }}
					@foreach ($dna_type_list as $id => $name)
					<div class="checkbox">
						<label>
							{{ Form::checkbox('library_source[]', $id) }}
							{{ $name }}
						</label>
					</div>
					@endforeach
				</div>

				<div id="results"></div>
			{{ Form::close() }}				
		</div>
		</div>

		<div class="col-md-10">
			<div class="data_container_box">
				<b>Active filters:</b>
				<?php /*
				@foreach ($filters as $filter)
				<span class="data_text_box">
					{{$filter}}
				</span>
				@endforeach
				*/ ?>
			</div>
			<div class="data_container_box">
				<b>Query breadth</b>:
				<?php /*
				<span class="data_text_box">
					{{$totalRepositories}} remote repositories
				</span>
				<span class="data_text_box">
					{{$totalLabs}} research labs
				</span>
				<span class="data_text_box">
					{{$totalStudies}} studies
				</span>
				<span class="data_text_box">
					{{$totalSamples}} samples
				</span>
				<span class="data_text_box">
					{{number_format($totalSequences)}} sequences
				</span>
				*/ ?>
			</div>
			<div class="data_container_box">
				<b>Query result:</b>
				<?php /*
				<span class="data_text_box">
					{{$nFilteredSamples}} samples
				</span>
				<span class="data_text_box">
					{{number_format($nFilteredSequences)}} sequences.
				</span>
				*/ ?>
			</div>
			<div class="data_container_box">
				<div id="sample_charts" class="charts">
					<div class="row">
						<div class="col-md-2 chart" id="sample_chart1"></div>
						<div class="col-md-2 chart" id="sample_chart2"></div>
						<div class="col-md-2 chart" id="sample_chart3"></div>
						<div class="col-md-2 chart" id="sample_chart4"></div>
						<div class="col-md-2 chart" id="sample_chart5"></div>
						<div class="col-md-2 chart" id="sample_chart6"></div>
					</div>
				</div>
			</div>

			<div class="data_container_box">
			@if (! empty($sample_list))
			{{ Form::open(array('url' => 'sequences', 'role' => 'form', 'method' => 'get')) }}

				<?php
					$max_count = 10;
				?>

				<?php /* if ($nFilteredSamples > $max_count): ?>
					{{ Form::submit('View all samples ('.(string)($nFilteredSamples-$max_count).' more)', array('class' => 'btn btn-primary show-more-samples-button', 'disabled' => 'disabled')) }}
				<?php endif	*/ ?>

				{{ Form::submit('Browse sequences from selected samples', array('class' => 'btn btn-primary browse-seq-data-button', 'disabled' => 'disabled')) }}

				<table class="table table-striped sample_list">
					<thead>
						<tr>
							<th>{{ Form::checkbox('select_all') }}</th>
							<th>Repository</th>
							<th>{{ __('sp.lab_name') }}</th>
							<th>{{ __('sp.study_title') }}</th>
							<th>{{ __('sp.subject_id') }}</th>
							<th>Sequences</th>
							<th>{{ __('sp.sample_id') }}</th>
							<th>{{ __('sp.tissue') }}</th>
							<th>{{ __('sp.cell_subset') }}</th>
							<th>{{ __('sp.cell_phenotype') }}</th>
							<th>{{ __('sp.library_source') }}</th>
						</tr>
					</thead>
					<tbody>
						<?php
							$count = 0;
						?>
						@foreach ($sample_list as $sample)
						<tr>
							<td>{{ Form::checkbox('project_sample_id_list_' . $sample->rest_service_id . '[]', $sample->ir_project_sample_id) }}</td>
							<td class="text-nowrap">
								<span title="{{ $sample->rest_service_name }}">
									{{ str_limit($sample->rest_service_name, $limit = 16, $end = '...') }}
								</span>
							</td>
							<td class="text-nowrap">
									<span title="{{ $sample->lab_name }}">
									{{ str_limit($sample->lab_name, $limit = 20, $end = '...') }}
									</span>
							</td>
							<td>
								<?php if (isset($sample->sra_accession)): ?>
									<a href="https://trace.ncbi.nlm.nih.gov/Traces/sra/?study={{ $sample->sra_accession }}" title="{{ $sample->study_title }}">
										{{ str_limit($sample->study_title, $limit = 20, $end = '...') }}
									</span>
								<?php elseif (isset($sample->study_title)): ?>
									<span title="{{ $sample->study_title }}">
									{{ str_limit($sample->study_title, $limit = 20, $end = '...') }}
									</span>							
								<?php endif ?>
							</td>
							<td>
								@isset($sample->subject_id)
									<span title="{{ $sample->subject_id }}">
									{{ str_limit($sample->subject_id, $limit = 12, $end = '...') }}
									</span>
								@endisset
							</td>							
						
							<td>
								@isset($sample->ir_sequence_count)
									@if ($sample->ir_sequence_count > 0)
										<a href="sequences?project_sample_id_list_{{ $sample->rest_service_id }}[]={{ $sample->ir_project_sample_id }}">
											<span class="label label-primary">{{number_format($sample->ir_sequence_count, 0 ,'.' ,',') }}</span>
										</a>
									@endif
								@endisset
							</td>
							<td>
								@isset($sample->sample_id)
									<span title="{{ $sample->sample_id }}">
									{{ str_limit($sample->sample_id, $limit = 12, $end = '...') }}
									</span>
								@endisset
							</td>	
							<td>
								@isset($sample->tissue)
									<span title="{{ $sample->tissue }}">
									{{ str_limit($sample->tissue, $limit = 12, $end = '...') }}
									</span>
								@endisset
							</td>
							<td>
								@isset($sample->cell_subset)
									<span title="{{ $sample->cell_subset }}">
									{{ str_limit($sample->cell_subset, $limit = 12, $end = '...') }}
									</span>
								@endisset
							<td>
								@isset($sample->cell_phenotype)
									<span title="{{ $sample->cell_phenotype }}">
									{{ str_limit($sample->cell_phenotype, $limit = 12, $end = '...') }}
									</span>
								@endisset
							</td>
							<td>
								@isset($sample->library_source)
									<span title="{{ $sample->library_source }}">
									{{ str_limit($sample->library_source, $limit = 12, $end = '...') }}
									</span>
								@endisset
							</td>
						</tr>
						<?php /*
							$count = $count + 1;
							if ($count >= $max_count) break;
						*/ ?>
						@endforeach
					</tbody>
				</table>

				<input type="hidden" name="project_id_list" />
				
				<?php /*if ($nFilteredSamples > $max_count): */?>
					<?php /*
					{{ Form::submit('View all samples ('.(string)($nFilteredSamples-$max_count).' more)', array('class' => 'btn btn-primary show-more-samples-button', 'disabled' => 'disabled')) }}
					*/ ?>
				<?php /* endif	*/ ?>
				{{ Form::submit('Browse sequences from selected samples', array('class' => 'btn btn-primary browse-seq-data-button', 'disabled' => 'disabled')) }}

			{{ Form::close() }}
			@endif
			</div>

		</div>
	</div>
</div>

<script>
/*
// v2 names
var graphFields = [
        "study_type", "organism", "disease_state_sample",
        "tissue", "cell_subset", "library_source"
    ];
// v1 names
var graphFields = [
        "project_type", "subject_species", "disease_state_name",
        "sample_source_name", "ireceptor_cell_subset_name", "dna_type"
    ];
*/
var graphFields = [
        "project_type", "subject_species", "disease_state_name",
        "sample_source_name", "ireceptor_cell_subset_name", "dna_type"
    ];
var graphNames = [
        "Study Type", "Organism", "Sample Disease State",
        "Sample Type", "Cell Subset", "Target Substrate"
    ];
var graphDIV = "sample_chart";
var graphInternalLabels = true;
var graphData = {!! $sample_list_json !!};
</script>
@stop
