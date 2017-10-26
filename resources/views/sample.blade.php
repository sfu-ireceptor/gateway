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
					{{ Form::label('subject_id', __('sp.subject_id')) }}
					{{ Form::text('subject_id', '', array('class' => 'form-control')) }}
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
					{{ Form::label('sample_id', 'Sample Record') }}
					{{ Form::text('sample_id', '', array('class' => 'form-control')) }}
				</div>

				<div class="form-group">
					{{ Form::label('cell_subset', 'Cell Type') }}
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
					{{ Form::label('tissue', 'Sample Source') }}
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


				<div id="results"></div>
				{{ Form::submit('Search samples', array('class' => 'btn btn-primary search_samples')) }}
			{{ Form::close() }}				
		</div>

		<div class="col-md-10">
			
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
							<td>{{ Form::checkbox('ir_project_sample_id_list_' . $sample->rest_service_id . '[]', $sample->ir_project_sample_id) }}</td>
							<td class="text-nowrap">{{ $sample->rest_service_name }}</td>
							<td class="text-nowrap">
								@isset($sample->lab_name)
									<span title="{{ $sample->lab_name }}">
									{{ str_limit($sample->lab_name, $limit = 40, $end = '...') }}
									</span>
								@endisset
							</td>
							<td>
								@if(isset($sample->sra_accession))
									<a href="https://trace.ncbi.nlm.nih.gov/Traces/sra/?study={{ $sample->sra_accession }}" title="{{ $sample->study_title }}">
										{{ str_limit($sample->study_title, $limit = 50, $end = '...') }}
									</span>
								@elseif(isset($sample->study_title))
									<span title="{{ $sample->study_title }}">
									{{ str_limit($sample->study_title, $limit = 50, $end = '...') }}
									</span>							
								@endif
							</td>
							<td>
								@isset($sample->sample_id)
									{{ $sample->sample_id }}
								@endisset
							</td>
							<td>
								@isset($sample->ir_sequence_count)
									@if ($sample->ir_sequence_count > 0)
									<a href="sequences?ir_project_sample_id_list_{{ $sample->rest_service_id }}[]={{ $sample->ir_project_sample_id }}">
										<span class="label label-primary">{{number_format($sample->ir_sequence_count, 0 ,'.' ,',') }}</span>
									</a>
									@endif
								@endisset
							</td>
							<td>
								@isset($sample->subject_id)
									{{ $sample->subject_id }}
								@endisset
							</td>
							<td>
								@isset($sample->tissue)
									{{ $sample->tissue }}
								@endisset
							</td>
							<td>
								@isset($sample->cell_subset)
									{{ $sample->cell_subset }}
								@endisset
							</td>
							<td>
								@isset($sample->ir_lab_cell_subset_name)
									{{ $sample->ir_lab_cell_subset_name }}
								@endisset
							</td>
							<td>
								@isset($sample->cell_phenotype)
									{{ $sample->cell_phenotype }}
								@endisset
							</td>
							<td>
								@isset($sample->library_source)
									{{ $sample->library_source }}
								@endisset
							</td>
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
