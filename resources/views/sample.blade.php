@extends('template')

@section('title', 'Search samples')

@section('content')
<div class="container-fluid sample_container">
	<div class="row">
		<div class="col-md-12">
			<ul class="nav nav-tabs nav-justified samples_sequences_nav">
				<li role="presentation" class="active"><a href="#">Samples</a></li>
				<li role="presentation"><a href="/sequences">Sequences</a></li>
			</ul>
		</div>
	</div>

	<div class="row">
		<div class="col-md-2">
			{{ Form::open(array('url' => 'samples', 'role' => 'form', 'method' => 'get', 'class' => 'sample_search')) }}
				<input type="hidden" name="project_id_list" />

			    <div class="form-group">
					{{ Form::label('subject_code', 'Subject Record') }}
					{{ Form::text('subject_code', '', array('class' => 'form-control')) }}
				</div>

				<div class="form-group">
					{{ Form::label('subject_gender', 'Gender') }}
					{{ Form::select('subject_gender', $subject_gender_list, '', array('class' => 'form-control')) }}
				</div>

			    <div class="form-group">
					{{ Form::label('subject_ethnicity', 'Ethnicity') }}
					{{ Form::select('subject_ethnicity', $subject_ethnicity_list, '', array('class' => 'form-control')) }}
				</div>

				 <div class="form-group">
					{{ Form::label('subject_age_min', 'Age') }}
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
					{{ Form::label('sample_name', 'Sample Record') }}
					{{ Form::text('sample_name', '', array('class' => 'form-control')) }}
				</div>

				<div class="form-group">
					{{ Form::label('ireceptor_cell_subset_name', 'Cell Type') }}
					@foreach ($ireceptor_cell_subset_name_list as $id => $name)
					<div class="checkbox">
						<label>
						{{ Form::checkbox('ireceptor_cell_subset_name[]', $id) }}
						{{ $name }}
						</label>
					</div>
					@endforeach
				</div>

			    <div class="form-group">
					{{ Form::label('sample_source_name', 'Sample Source') }}
					@foreach ($sample_source_list as $id => $name)
					<div class="checkbox">
						<label>
						{{ Form::checkbox('sample_source_name[]', $id) }}
						{{ $name }}
						</label>
					</div>
					@endforeach
				</div>

				 <div class="form-group">
					{{ Form::label('dna_type', 'DNA Type') }}
					@foreach ($dna_type_list as $id => $name)
					<div class="checkbox">
						<label>
							{{ Form::checkbox('dna_type[]', $id) }}
							{{ $name }}
						</label>
					</div>
					@endforeach
				</div>

				<div class="bs-example">
					<fieldset class="first">
						<legend>Data Sites</legend>
						<div id="rest_service_list">
							<ul>
								@foreach ($rest_service_list as $rs)
							     <li>
							     	{{ $rs->name }}
								    <ul>
							 			@foreach ($rs->labs as $lab)
										<li>
											Lab: {{ $lab->name }}
										    <ul>
								     			@foreach ($lab->projects as $project)

												<li id="{{ $project->id }}" data-jstree='{"selected":{{ in_array($project->id, explode(',', old('project_id_list'))) ? 'true' : 'false'}}}'>
													Project: {{ $project->name }}
													{{-- <span class="sra">{{ $project->sra_accession }}</span> --}}
												</li>
										 		@endforeach
									 		</ul>
										</li>
								 		@endforeach
							 		</ul>
							     </li>
								@endforeach
							</ul>
						</div>
					</fieldset>
				</div>

				<div id="results"></div>
				{{ Form::submit('Search samples', array('class' => 'btn btn-primary search_samples')) }}
			{{ Form::close() }}				
		</div>

		<div class="col-md-10">
			<div class="samples_filters">
				<b>Active filters:</b>
				@foreach ($filters as $filter)
				<span class="filter_box">
					{{$filter}}
				</span>
				@endforeach
			</div>
			<div class="samples_filters">
				<b>Query breadth</b>:
				<span class="filter_box">
					{{$totalRepositories}} remote repositories
				</span>
				<span class="filter_box">
					{{$totalLabs}} research labs
				</span>
				<span class="filter_box">
					{{$totalStudies}} studies
				</span>
				<span class="filter_box">
					{{$totalSamples}} samples
				</span>
				<span class="filter_box">
					{{number_format($totalSequences)}} sequences
				</span>
			</div>
			<div class="samples_filters">
				<b>Query result:</b>
				<span class="filter_box">
					{{$nFilteredSamples}} samples
				</span>
				<span class="filter_box">
					{{number_format($nFilteredSequences)}} sequences.
				</span>
			</div>
			<!-- statistics box -->
			<div class="samples_stats">


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

			@if (! empty($sample_list))
			{{ Form::open(array('url' => 'sequences', 'role' => 'form', 'method' => 'get')) }}


				{{ Form::submit('Browse sequences', array('class' => 'btn btn-primary browse-seq-data-button', 'disabled' => 'disabled')) }}

				<table class="table table-striped sample_list">
					<thead>
						<tr>
							<th></th>
							<th>Data Site</th>
							<th>Lab</th>
							<th>Project</th>
							<th>Sample Record</th>
							<th>Sequences</th>
							<th>Subject Record</th>
							<th>Sample Source</th>
							<th>Cell Type</th>
							<th>User-defined Cell Type</th>
							<th>Markers</th>
							<th>DNA Type</th>
						</tr>
					</thead>
					<tbody>
						@foreach ($sample_list as $sample)
						<tr>
							<td>{{ Form::checkbox('project_sample_id_list_' . $sample->rs_id . '[]', $sample->project_sample_id) }}</td>
							<td class="text-nowrap">{{ $sample->rs_name }}</td>
							<td class="text-nowrap">
									<span title="{{ $sample->lab_name }}">
									{{ str_limit($sample->lab_name, $limit = 40, $end = '...') }}
									</span>
							</td>
							<td>
								<?php if (isset($sample->sra_accession)): ?>
									<a href="https://trace.ncbi.nlm.nih.gov/Traces/sra/?study={{ $sample->sra_accession }}" title="{{ $sample->project_name }}">
										{{ str_limit($sample->project_name, $limit = 50, $end = '...') }}
									</span>
								<?php else: ?>
									<span title="{{ $sample->project_name }}">
									{{ str_limit($sample->project_name, $limit = 50, $end = '...') }}
									</span>							
								<?php endif ?>
							</td>
							<td>{{ $sample->sample_name }}</td>
							<td>
								@if ($sample->sequence_count > 0)
								<a href="sequences?project_sample_id_list_{{ $sample->rs_id }}[]={{ $sample->project_sample_id }}">
									<span class="label label-primary">{{number_format($sample->sequence_count, 0 ,'.' ,',') }}</span>
								</a>
								@endif
							</td>
							<td>{{ $sample->subject_code }}</td>
							<td>{{ $sample->sample_source_name }}</td>
							<td>{{ $sample->ireceptor_cell_subset_name }}</td>
							<td>{{ $sample->lab_cell_subset_name }}</td>
							<td>
								{{ $sample->marker_1 }}
								{{ $sample->marker_2 }}
								{{ $sample->marker_3 }}
								{{ $sample->marker_4 }}
								{{ $sample->marker_5 }}
								{{ $sample->marker_6 }}
							</td>
							<td>{{ $sample->dna_type }}</td>
						</tr>
						@endforeach
					</tbody>
				</table>

				<input type="hidden" name="project_id_list" />
				
				{{ Form::submit('Browse sequences', array('class' => 'btn btn-primary browse-seq-data-button', 'disabled' => 'disabled')) }}

			{{ Form::close() }}
			@endif

		</div>
	</div>
</div>

<script>
var data = {!! $sample_list_json !!};
</script>
@stop
