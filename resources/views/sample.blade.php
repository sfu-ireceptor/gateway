@extends('template')

@section('title', 'Search samples')

@section('content')

<div class="container-fluid sample_container">

	<div class="row">
		<div class="col-md-2 filters">

			<h3>Filters</h3>

			{{ Form::open(array('url' => 'samples', 'role' => 'form', 'method' => 'get', 'class' => 'sample_search')) }}
				<input type="hidden" name="project_id_list" />

				<div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
					<div class="panel panel-default">
						<div class="panel-heading" role="tab" id="headingOne">
							<h4 class="panel-title">
								<a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
									Filter by subject
								</a>
							</h4>
						</div>
						<div id="collapseOne" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingOne">
							<div class="panel-body">
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
									{{ Form::label('study_id', __('sp.study_id')) }}
									{{ Form::text('study_id', '', array('class' => 'form-control')) }}
								</div>

							    <div class="form-group">
									{{ Form::label('study_title', __('sp.study_title')) }}
									{{ Form::text('study_title', '', array('class' => 'form-control')) }}
								</div>

							    <div class="form-group">
									{{ Form::label('study_description', __('sp.study_description')) }}
									{{ Form::text('study_description', '', array('class' => 'form-control')) }}
								</div>

							    <div class="form-group">
									{{ Form::label('lab_name', __('sp.lab_name')) }}
									{{ Form::text('lab_name', '', array('class' => 'form-control')) }}
								</div>

							    <div class="form-group">
									{{ Form::label('organism', __('sp.organism')) }}
									{{ Form::text('organism', '', array('class' => 'form-control')) }}
								</div>

								<div class="form-group">
									{{ Form::label('ir_subject_age_min', __('sp.age')) }}
									<div class="row">
										<div class="col-md-6">
											{{ Form::text('ir_subject_age_min', '', array('class' => 'form-control', 'placeholder' => 'From')) }}
										</div>
										<div class="col-md-6">
											{{ Form::text('ir_subject_age_max', '', array('class' => 'form-control', 'placeholder' => 'To')) }}
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="panel panel-default">
						<div class="panel-heading" role="tab" id="headingTwo">
							<h4 class="panel-title">
								<a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
									Filter by sample
								</a>
							</h4>
						</div>
						<div id="collapseTwo" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingTwo">
							<div class="panel-body">
								<div class="form-group">
									{{ Form::label('sample_id', __('sp.sample_id')) }}
									{{ Form::text('sample_id', '', array('class' => 'form-control')) }}
								</div>

								<div class="form-group">
									{{ Form::label('disease_state_sample', __('sp.disease_state_sample')) }}
									{{ Form::text('disease_state_sample', '', array('class' => 'form-control')) }}
								</div>

								<div class="form-group">
									{{ Form::label('cell_phenotype', __('sp.cell_phenotype')) }}
									{{ Form::text('cell_phenotype', '', array('class' => 'form-control')) }}
								</div>

								<div class="form-group">
									{{ Form::label('sequencing_platform', __('sp.sequencing_platform')) }}
									{{ Form::text('sequencing_platform', '', array('class' => 'form-control')) }}
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
									{{ Form::label('template_class', __('sp.template_class')) }}
									@foreach ($dna_type_list as $id => $name)
									<div class="checkbox">
										<label>
											{{ Form::checkbox('template_class[]', $id) }}
											{{ $name }}
										</label>
									</div>
									@endforeach
								</div>
							</div>
						</div>
					</div>
				</div>

				<p>
					{{ Form::submit('Apply filters', array('class' => 'btn btn-primary search_samples')) }}
				</p>
			    
			{{ Form::close() }}
		</div>

		<div class="col-md-10">
			<h1>Samples <small>Filter for and select samples to view their sequences</small></h1>

			<p>
				<strong>Active filters:</strong>
			</p>

			<p>
				<strong>{{ $total_filtered_samples }} samples returned from:</strong>
				<span class="data_text_box">
					{{ $total_filtered_repositories }} remote repositories
				</span>
				<span class="data_text_box">
					{{ $total_filtered_labs }} research labs
				</span>
				<span class="data_text_box">
					{{ $total_filtered_studies }} studies
				</span>
			</p>

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
					$max_count = 1000;
				?>

				@if ($total_filtered_samples > $max_count)
					{{ Form::submit('View all samples ('.(string)($total_filtered_samples-$max_count).' more)', array('class' => 'btn btn-primary show-more-samples-button', 'disabled' => 'disabled')) }}
				@endif

				{{ Form::submit('Browse sequences from selected samples', array('class' => 'btn btn-primary browse-seq-data-button', 'disabled' => 'disabled')) }}

				<table class="table table-striped sample_list table-condensed">
					<thead>
						<tr>
							<th>{{ Form::checkbox('select_all') }}</th>
							<th>Repository</th>
							<th>@lang('sp.lab_name')</th>
							<th>@lang('sp.study_title')</th>
							<th>@lang('sp.subject_id')</th>
							<th>Sequences</th>
							<th>@lang('sp.sample_id')</th>
							<th>@lang('sp.tissue')</th>
							<th>@lang('sp.cell_subset')</th>
							<th>@lang('sp.cell_phenotype')</th>
							<th>@lang('sp.library_source')</th>
						</tr>
					</thead>
					<tbody>
						<?php
							$count = 0;
						?>
						@foreach ($sample_list as $sample)
						<tr>
							<td>{{ Form::checkbox('ir_project_sample_id_list_' . $sample->rest_service_id . '[]', $sample->ir_project_sample_id) }}</td>
							<td class="text-nowrap">
								<span title="{{ $sample->rest_service_name }}">
									{{ str_limit($sample->rest_service_name, $limit = 9, $end = '‥') }}
								</span>
							</td>
							<td class="text-nowrap">
								@isset($sample->lab_name)
									<span title="{{ $sample->lab_name }}">
									{{ str_limit($sample->lab_name, $limit = 10, $end = '‥') }}
									</span>
								@endif
							</td>
							<td>
								@if (isset($sample->sra_accession))
									<a href="https://trace.ncbi.nlm.nih.gov/Traces/sra/?study={{ $sample->sra_accession }}" title="{{ $sample->study_title }}">
										{{ str_limit($sample->study_title, $limit = 30, $end = '‥') }}
									</span>
								@elseif (isset($sample->study_title))
									<span title="{{ $sample->study_title }}">
									{{ str_limit($sample->study_title, $limit = 30, $end = '‥') }}
									</span>							
								@endif
							</td>
							<td>
								@isset($sample->subject_id)
									<span title="{{ $sample->subject_id }}">
									{{ str_limit($sample->subject_id, $limit = 15, $end = '‥') }}
									</span>
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
								@isset($sample->sample_id)
									<span title="{{ $sample->sample_id }}">
									{{ str_limit($sample->sample_id, $limit = 12, $end = '') }}
									</span>
								@endisset
							</td>	
							<td>
								@isset($sample->tissue)
									<span title="{{ $sample->tissue }}">
									{{ str_limit($sample->tissue, $limit = 12, $end = '‥') }}
									</span>
								@endisset
							</td>
							<td>
								@isset($sample->cell_subset)
									<span title="{{ $sample->cell_subset }}">
									{{ str_limit($sample->cell_subset, $limit = 12, $end = '‥') }}
									</span>
								@endisset
							<td>
								@isset($sample->cell_phenotype)
									<span title="{{ $sample->cell_phenotype }}">
									{{ str_limit($sample->cell_phenotype, $limit = 12, $end = '‥') }}
									</span>
								@endisset
							</td>
							<td>
								@isset($sample->template_class)
									<span title="{{ $sample->template_class }}">
									{{ str_limit($sample->template_class, $limit = 12, $end = '‥') }}
									</span>
								@endisset
							</td>
						</tr>
						<?php 
							$count = $count + 1;
							if ($count >= $max_count) break;
						?>
						@endforeach
					</tbody>
				</table>

				<input type="hidden" name="project_id_list" />
				
				@if ($total_filtered_samples > $max_count):
					{{ Form::submit('View all samples ('.(string)($total_filtered_samples-$max_count).' more)', array('class' => 'btn btn-primary show-more-samples-button', 'disabled' => 'disabled')) }}
				@endif
				{{ Form::submit('Browse sequences from selected samples', array('class' => 'btn btn-primary browse-seq-data-button', 'disabled' => 'disabled')) }}

			{{ Form::close() }}
			@endif
			</div>

		</div>
	</div>
</div>

<script>
	var graphFields = [
	        "@lang('v2.study_description')", "organism", "disease_state_sample",
	        "tissue", "cell_subset", "template_class"
	    ];
	var graphNames = [
	        "@lang('sp.study_description')", "Organism", "Sample Disease State",
	        "Sample Type", "Cell Subset", "Target Substrate"
	    ];
	var graphDIV = "sample_chart";
	var graphInternalLabels = true;
	var graphData = {!! $sample_list_json !!};
</script>
@stop
