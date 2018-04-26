@extends('template')

@section('title', 'Metadata Search')

@section('content')

<div class="container-fluid sample_container">

	<h1>Metadata Search</h1>
	<p class="sh1">Filter by study/subject/sample and choose samples to analyze relevant sequence data</p>

	<div class="row loading_contents">
		<div class="col-md-2 filters">

			<h3 class="first">Filters</h3>

			{{ Form::open(array('url' => 'samples', 'role' => 'form', 'method' => 'post', 'class' => 'sample_search')) }}
				<input type="hidden" name="project_id_list" />

				<div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
					<div class="panel panel-default">
						<div class="panel-heading" role="tab" id="headingOne">
							<h4 class="panel-title">
								<a role="button" class="{{ in_array('0', $open_filter_panel_list) ? '' : 'collapsed' }}" data-toggle="collapse" data-parent="#accordion" href="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
									Filter by study
								</a>
							</h4>
						</div>
						<div id="collapseOne" class="panel-collapse collapse {{ in_array('0', $open_filter_panel_list) ? 'in' : '' }}" role="tabpanel" aria-labelledby="headingOne">
							<div class="panel-body">

							    <div class="form-group">
									{{ Form::label('study_id', __('short.study_id')) }}
									@include('help', ['id' => 'study_id'])
									{{ Form::text('study_id', '', array('class' => 'form-control')) }}
								</div>

							    <div class="form-group">
									{{ Form::label('study_title', __('short.study_title')) }}
									@include('help', ['id' => 'study_title'])
									{{ Form::text('study_title', '', array('class' => 'form-control')) }}
								</div>

							    <div class="form-group">
									{{ Form::label('study_description', __('short.study_description')) }}
									@include('help', ['id' => 'study_description'])
									{{ Form::text('study_description', '', array('class' => 'form-control')) }}
								</div>

							    <div class="form-group">
									{{ Form::label('study_group_description', __('short.study_group_description')) }}
									@include('help', ['id' => 'study_group_description'])
									{{ Form::text('study_group_description', '', array('class' => 'form-control')) }}
								</div>

							    <div class="form-group">
									{{ Form::label('lab_name', __('short.lab_name')) }}
									@include('help', ['id' => 'lab_name'])
									{{ Form::text('lab_name', '', array('class' => 'form-control')) }}
								</div>

								<p class="button_container">
									{{ Form::submit('Apply filters →', array('class' => 'btn btn-primary search_samples loading')) }}
								</p>
							</div>
						</div>
					</div>
					<div class="panel panel-default">
						<div class="panel-heading" role="tab" id="headingTwo">
							<h4 class="panel-title">
								<a role="button" class="{{ in_array('1', $open_filter_panel_list) ? '' : 'collapsed' }}" data-toggle="collapse" data-parent="#accordion" href="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
									Filter by subject
								</a>
							</h4>
						</div>
						<div id="collapseTwo" class="panel-collapse collapse {{ in_array('1', $open_filter_panel_list) ? 'in' : '' }}" role="tabpanel" aria-labelledby="headingTwo">
							<div class="panel-body">
							    <div class="form-group">
									{{ Form::label('subject_id', __('short.subject_id')) }}
									@include('help', ['id' => 'subject_id'])
									{{ Form::text('subject_id', '', array('class' => 'form-control')) }}
								</div>

							    <div class="form-group">
									{{ Form::label('organism', __('short.organism')) }}
									@include('help', ['id' => 'organism'])
									{{ Form::text('organism', '', array('class' => 'form-control')) }}
								</div>

								<div class="form-group">
									{{ Form::label('sex', __('short.sex')) }}
									@include('help', ['id' => 'sex'])
									{{ Form::select('sex', $subject_gender_list, '', array('class' => 'form-control')) }}
								</div>

							    <div class="form-group">
									{{ Form::label('ethnicity', __('short.ethnicity')) }}
									@include('help', ['id' => 'ethnicity'])
									@foreach ($subject_ethnicity_list as $id => $name)
									<div class="checkbox">
										<label>
										{{ Form::checkbox('ethnicity[]', $id) }}
										{{ $name }}
										</label>
									</div>
									@endforeach
								</div>

								<div class="form-group">
									{{ Form::label('ir_subject_age_min', __('short.age')) }}
									@include('help', ['id' => 'age'])
									<div class="row">
										<div class="col-md-6">
											{{ Form::text('ir_subject_age_min', '', array('class' => 'form-control', 'placeholder' => 'From')) }}
										</div>
										<div class="col-md-6">
											{{ Form::text('ir_subject_age_max', '', array('class' => 'form-control', 'placeholder' => 'To')) }}
										</div>
									</div>
								</div>

								<p class="button_container">
									{{ Form::submit('Apply filters →', array('class' => 'btn btn-primary search_samples loading')) }}
								</p>

							</div>
						</div>
					</div>
					<div class="panel panel-default">
						<div class="panel-heading" role="tab" id="headingThree">
							<h4 class="panel-title">
								<a class="{{ in_array('2', $open_filter_panel_list) ? '' : 'collapsed' }}" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
									Filter by sample
								</a>
							</h4>
						</div>
						<div id="collapseThree" class="panel-collapse collapse {{ in_array('2', $open_filter_panel_list) ? 'in' : '' }}" role="tabpanel" aria-labelledby="headingThree">
							<div class="panel-body">
								<div class="form-group">
									{{ Form::label('sample_id', __('short.sample_id')) }}
									@include('help', ['id' => 'sample_id'])
									{{ Form::text('sample_id', '', array('class' => 'form-control')) }}
								</div>

								<div class="form-group">
									{{ Form::label('cell_subset', __('short.cell_subset')) }}
									@include('help', ['id' => 'cell_subset'])
								    {{ Form::select('cell_subset[]', $cell_type_list, '', array('class' => 'form-control multiselect-ui', 'multiple' => 'multiple')) }}
								</div>

							    <div class="form-group">
									{{ Form::label('tissue', __('short.tissue')) }}
									@include('help', ['id' => 'tissue'])
								    {{ Form::select('tissue[]', $sample_source_list, '', array('class' => 'form-control multiselect-ui', 'multiple' => 'multiple')) }}
								</div>

								 <div class="form-group">
									{{ Form::label('template_class', __('short.template_class')) }}
									@include('help', ['id' => 'template_class'])
									@foreach ($dna_type_list as $id => $name)
									<div class="checkbox">
										<label>
											{{ Form::checkbox('template_class[]', $id) }}
											{{ $name }}
										</label>
									</div>
									@endforeach
								</div>

								<div class="form-group">
									{{ Form::label('cell_phenotype', __('short.cell_phenotype')) }}
									@include('help', ['id' => 'cell_phenotype'])
									{{ Form::text('cell_phenotype', '', array('class' => 'form-control')) }}
								</div>

								<div class="form-group">
									{{ Form::label('disease_state_sample', __('short.disease_state_sample')) }}
									@include('help', ['id' => 'disease_state_sample'])
									{{ Form::text('disease_state_sample', '', array('class' => 'form-control')) }}
								</div>

								<div class="form-group">
									{{ Form::label('sequencing_platform', __('short.sequencing_platform')) }}
									@include('help', ['id' => 'sequencing_platform'])
									{{ Form::text('sequencing_platform', '', array('class' => 'form-control')) }}
								</div>

								<p class="button_container">
									{{ Form::submit('Apply filters →', array('class' => 'btn btn-primary search_samples loading')) }}
								</p>

							</div>
						</div>
					</div>
				</div>
			    
			{{ Form::close() }}
		</div>

		<div class="col-md-10">

			<!-- Active filters -->
			@if ( ! empty($filter_fields))
				<div class="active_filters">
					<h3>Active filters</h3>
					@foreach($filter_fields as $filter_key => $filter_value)
						<span title= "@lang('short.' . $filter_key): {{$filter_value}}", class="label label-default">
							@lang('short.' . $filter_key)
						</span>
					@endforeach
					<a href="/samples" class="remove_filters">
						Remove filters
					</a>

					<a class="bookmark" href="/system/" data-uri="{{ $url }}">
						@if ($bookmark_id)
							<button type="button" class="btn btn-success" aria-label="Bookmark" data-id="{{ $bookmark_id }}">
							  <span class="glyphicon glyphicon-star" aria-hidden="true"></span>
							  <span class="text">Bookmarked</span>
							</button>
						@else
							<button type="button" class="btn btn-primary" aria-label="Bookmark">
							  <span class="glyphicon glyphicon-star-empty" aria-hidden="true"></span>
							  <span class="text">Bookmark this search</span>
							</button>
						@endif
					</a>

				</div>
			@endif	
		

			@if (empty($sample_list))
				<div class="no_results">
					<h2>No Results</h2>
					<p>Remove a filter or <a href="/samples">remove all filters</a> to return results.</p>
				</div>
			@else
				<!-- Statistics -->
				<h3 class="{{ empty($filter_fields) ? 'first' : '' }}">Search results statistics</h3>
				<div class="statistics">
					<p>
						<strong>
							{{number_format($total_filtered_sequences)}} sequences
							({{ $total_filtered_samples }} {{ str_plural('sample', $total_filtered_samples)}})
						</strong>
						returned from

						<a href="#" data-toggle="modal" data-target="#myModal">
							{{ $total_filtered_repositories }} remote {{ str_plural('repository', $total_filtered_repositories)}},
							{{ $total_filtered_labs }} research {{ str_plural('lab', $total_filtered_labs)}},
							{{ $total_filtered_studies }} {{ str_plural('study', $total_filtered_studies)}}
						</a>
					</p>

					<!-- repos/labs/studies details popup -->
					@include('rest_service_list', ['total_repositories' => $total_filtered_repositories, 'total_labs' => $total_filtered_labs, 'total_projects' => $total_filtered_studies])

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
			@endif


			@if (! empty($sample_list))
			{{ Form::open(array('url' => 'sequences', 'role' => 'form', 'method' => 'post', 'class' => 'sample_form')) }}

				<h3>Individual Samples</h3>
				<p class="table_info">
					<span class="nb_selected_samples">{{ count($sample_list) }}</span> samples selected
					<a class="unselect_all_samples" href="#">Unselect All</a>
					<a class="select_all_samples" href="#">Select All</a>
					{{ Form::submit('Browse sequences from selected samples →', array('class' => 'btn btn-primary browse_sequences loading browse-seq-data-button')) }}
				</p>
				
				<table class="table table-striped sample_list table-condensed much_data table-bordered">
					<thead> 
						<tr>
							<th class="checkbox_cell">
{{-- 								<button type="button" class="btn btn-default btn-xs" aria-label="Left Align">
								  <span class="glyphicon glyphicon-edit" aria-hidden="true"></span>
								</button> --}}
							</th>
							<th>Repository</th>
							<th>@lang('short.lab_name')</th>
							<th>@lang('short.study_title')</th>
							<th>@lang('short.study_group_description')</th>
							<th>@lang('short.subject_id')</th>
							<th>Sequences</th>
							<th>@lang('short.tissue')</th>
							<th>@lang('short.cell_subset')</th>
							<th>@lang('short.cell_phenotype')</th>
							<th>@lang('short.sample_id')</th>
							<th>@lang('short.template_class')</th>
							<th>@lang('short.study_id')</th>
							<th>@lang('short.pub_ids')</th>
						</tr>
					</thead>
					<tbody>
						@foreach ($sample_list as $sample)
						<tr>
							<td class="checkbox_cell">
								@isset($sample->ir_sequence_count)
									@if ($sample->ir_sequence_count > 0)
										{{ Form::checkbox('ir_project_sample_id_list_' . $sample->rest_service_id . '[]', $sample->ir_project_sample_id, true) }}
									@endif
								@endisset
							</td>
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
								@isset($sample->study_title)
									@isset($sample->study_url)
										<a href="{{$sample->study_url}}" title="{{ $sample->study_title }}" target="_blank">
											{{ str_limit($sample->study_title, $limit = 25, $end = '‥') }}
										</a>
									@else
										<span title="{{ $sample->study_title }}">
											{{ str_limit($sample->study_title, $limit = 25, $end = '‥') }}
										</span>							
									@endisset
								@endisset
							</td>
							<td>
								@isset($sample->study_group_description)
									<span title="{{ $sample->study_group_description }}">
									{{ str_limit($sample->study_group_description, $limit = 15, $end = '‥') }}
									</span>
								@endisset
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
										<a href="sequences?ir_project_sample_id_list_{{ $sample->rest_service_id }}[]={{ $sample->ir_project_sample_id }}@if($sample_query_id != '')&amp;sample_query_id={{ $sample_query_id }}@endif">
											<span class="label label-primary">{{number_format($sample->ir_sequence_count, 0 ,'.' ,',') }}</span>
										</a>
									@endif
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
								@isset($sample->sample_id)
									<span title="{{ $sample->sample_id }}">
									{{ str_limit($sample->sample_id, $limit = 12, $end = '') }}
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
							<td>
								@isset($sample->study_id)
									<span title="{{ $sample->study_id }}">
									{{ str_limit($sample->study_id, $limit = 15, $end = '‥') }}
									</span>
								@endisset
							</td>		
							<td>
								@isset($sample->pub_ids)
									<span title="{{ $sample->pub_ids }}">
									{{ str_limit($sample->pub_ids, $limit = 15, $end = '‥') }}
									</span>
								@endisset
							</td>	
						</tr>
						@endforeach
					</tbody>
				</table>

				<input type="hidden" name="project_id_list" />
				<input type="hidden" name="sample_query_id" value="{{ $sample_query_id }}" />
				<p class="pull-right">
				{{ Form::submit('Browse sequences from selected samples →', array('class' => 'btn btn-primary browse-seq-data-button loading')) }}
				</p>
			{{ Form::close() }}
			@endif

		</div>
	</div>
</div>

<div class="loading_message">
	<h2>Loading...</h2>
	<p>This could take a few seconds to a few minutes.</p>
	<p class="text-right cancel_container">
		<a href="#" class="btn btn-warning cancel">
			<span class="glyphicon glyphicon-remove-circle" aria-hidden="true"></span>
			<span class="text loading">Cancel</span>
		</a>
	</p>
</div>

<script>
	var graphFields = [
	        "@lang('v2.study_description')", 
	        "@lang('v2.organism')",
	        "@lang('v2.disease_state_sample')", 
	        "@lang('v2.tissue')",
	        "@lang('v2.cell_subset')", 
	        "@lang('v2.template_class')"
	    ];
	var graphNames = [
	        "@lang('short.study_description')",
	        "@lang('short.organism')", 
	        "@lang('short.disease_state_sample')",
	        "@lang('short.tissue')", 
	        "@lang('short.cell_subset')", 
	        "@lang('short.template_class')"
	    ];
	var graphDIV = "sample_chart";
	var graphInternalLabels = true;
	var graphLabelLength = 10;
	var graphCountField = "ir_sequence_count";
	var graphData = {!! $sample_list_json !!};
</script>
@stop
