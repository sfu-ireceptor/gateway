@extends('template')

@section('title', 'Search samples')

@section('content')

<div class="container-fluid sample_container">

	<h1>Samples <small>Filter for and select samples to view their sequences</small></h1>

	<div class="row">
		<div class="col-md-2 filters">

			<h3>Filters</h3>

			{{ Form::open(array('url' => 'samples', 'role' => 'form', 'method' => 'post', 'class' => 'sample_search')) }}
				<input type="hidden" name="project_id_list" />

				<div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
					<div class="panel panel-default">
						<div class="panel-heading" role="tab" id="headingOne">
							<h4 class="panel-title">
								<a role="button" class="collapsed" data-toggle="collapse" data-parent="#accordion" href="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
									Filter by study
								</a>
							</h4>
						</div>
						<div id="collapseOne" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingOne">
							<div class="panel-body">

							    <div class="form-group">
									{{ Form::label('study_id', __('short.study_id')) }}
									{{ Form::text('study_id', '', array('class' => 'form-control')) }}
								</div>

							    <div class="form-group">
									{{ Form::label('study_title', __('short.study_title')) }}
									{{ Form::text('study_title', '', array('class' => 'form-control')) }}
								</div>

							    <div class="form-group">
									{{ Form::label('study_description', __('short.study_description')) }}
									{{ Form::text('study_description', '', array('class' => 'form-control')) }}
								</div>

							    <div class="form-group">
									{{ Form::label('study_group_description', __('short.study_group_description')) }}
									{{ Form::text('study_group_description', '', array('class' => 'form-control')) }}
								</div>

							    <div class="form-group">
									{{ Form::label('lab_name', __('short.lab_name')) }}
									{{ Form::text('lab_name', '', array('class' => 'form-control')) }}
								</div>

							</div>
						</div>
					</div>
					<div class="panel panel-default">
						<div class="panel-heading" role="tab" id="headingTwo">
							<h4 class="panel-title">
								<a role="button" class="collapsed" data-toggle="collapse" data-parent="#accordion" href="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
									Filter by subject
								</a>
							</h4>
						</div>
						<div id="collapseTwo" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingTwo">
							<div class="panel-body">
							    <div class="form-group">
									{{ Form::label('subject_id', __('short.subject_id')) }}
									{{ Form::text('subject_id', '', array('class' => 'form-control')) }}
								</div>

							    <div class="form-group">
									{{ Form::label('organism', __('short.organism')) }}
									{{ Form::text('organism', '', array('class' => 'form-control')) }}
								</div>

								<div class="form-group">
									{{ Form::label('sex', __('short.sex')) }}
									{{ Form::select('sex', $subject_gender_list, '', array('class' => 'form-control')) }}
								</div>

							    <div class="form-group">
									{{ Form::label('ethnicity', __('short.ethnicity')) }}
									{{ Form::select('ethnicity', $subject_ethnicity_list, '', array('class' => 'form-control')) }}
								</div>

								<div class="form-group">
									{{ Form::label('ir_subject_age_min', __('short.age')) }}
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
						<div class="panel-heading" role="tab" id="headingThree">
							<h4 class="panel-title">
								<a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
									Filter by sample
								</a>
							</h4>
						</div>
						<div id="collapseThree" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingThree">
							<div class="panel-body">
								<div class="form-group">
									{{ Form::label('sample_id', __('short.sample_id')) }}
									{{ Form::text('sample_id', '', array('class' => 'form-control')) }}
								</div>

								<div class="form-group">
									{{ Form::label('cell_subset', __('short.cell_subset')) }}
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
									{{ Form::label('tissue', __('short.tissue')) }}
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
									{{ Form::label('template_class', __('short.template_class')) }}
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
									{{ Form::text('cell_phenotype', '', array('class' => 'form-control')) }}
								</div>

								<div class="form-group">
									{{ Form::label('disease_state_sample', __('short.disease_state_sample')) }}
									{{ Form::text('disease_state_sample', '', array('class' => 'form-control')) }}
								</div>

								<div class="form-group">
									{{ Form::label('sequencing_platform', __('short.sequencing_platform')) }}
									{{ Form::text('sequencing_platform', '', array('class' => 'form-control')) }}
								</div>


							</div>
						</div>
					</div>
				</div>

				<p class="button_container">
					<a href="/samples" class="btn btn-default">
						<span class="glyphicon glyphicon-remove" aria-hidden="true"></span>
						<span class="text">Clear filters</span>
					</a>
					{{ Form::submit('Apply filters →', array('class' => 'btn btn-primary search_samples')) }}
				</p>
			    
			{{ Form::close() }}
		</div>

		<div class="col-md-10">
			<div class="data_container_box">
				<p>
					<strong>Aggregate Search Statistics</strong>
				</p>
				<p>
					Active {{ str_plural('filter', count($filter_fields))}}:
					@foreach($filter_fields as $filter_key => $filter_value)
						<span title= "@lang('short.' . $filter_key): {{$filter_value}}", class="label label-default">
							@lang('short.' . $filter_key)
						</span>
					@endforeach
					@if (empty($filter_fields))
						<em>none</em>
					@endif					
				</p>

				@if (empty($sample_list))
					<p>0 sequences returned.</p>
				@endif

				@if (! empty($sample_list))
					<p>
						{{number_format($total_filtered_sequences)}} sequences ({{ $total_filtered_samples }} {{ str_plural('sample', $total_filtered_samples)}}) returned from:
						<span title="{{ $filtered_repositories_names }}", class="data_text_box">
							{{ $total_filtered_repositories }} remote {{ str_plural('repository', $total_filtered_repositories)}}
						</span>
						<span class="data_text_box">
							{{ $total_filtered_labs }} research {{ str_plural('lab', $total_filtered_labs)}}
						</span>
						<span class="data_text_box">
							{{ $total_filtered_studies }} {{ str_plural('study', $total_filtered_studies)}}
						</span>
						<button type="button" class="btn btn-default btn-xs" data-toggle="modal" data-target="#myModal">
						  Show All
						</button>
					</p>

					<!-- Modal -->
					<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
					  <div class="modal-dialog" role="document">
					    <div class="modal-content">
					      <div class="modal-header">
					        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					        <h4 class="modal-title" id="myModalLabel">
					        	{{ $total_filtered_repositories }} remote {{ str_plural('repository', $total_filtered_repositories)}},
					        	{{ $total_filtered_labs }} research {{ str_plural('lab', $total_filtered_labs)}},
					        	{{ $total_filtered_studies }} {{ str_plural('study', $total_filtered_studies)}}
					        </h4>
					      </div>
					      <div class="modal-body">
					        
					        <div id="rest_service_list">
								<ul>
									@foreach ($rest_service_list as $rs_data)
								     <li  class="rs_node" data-jstree='{"opened":true, "disabled":true, "icon":"glyphicon glyphicon-home"}'>
								     	<span class="node_name">{{ $rs_data['rs']->name }}</span>
								     	<em>{{ human_number($rs_data['total_sequences']) }} sequences</em>
									    <ul>
								 			@foreach ($rs_data['study_tree'] as $lab)
											<li class="lab_node" data-jstree='{"opened":true, "disabled":true, "icon":"glyphicon glyphicon-education"}'>
												<span title="{{ $lab['name'] }}" class="lab_name">
													Lab:
													{{ str_limit($lab['name'], $limit = 64, $end = '‥') }}
												</span> 
												<em>{{ human_number($lab['total_sequences']) }} sequences</em>
											    <ul>
								 					@foreach ($lab['studies'] as $study)
								 						<li data-jstree='{"icon":"glyphicon glyphicon-book", "disabled":true}'>
								 							<span>
																Study:
																@if (isset($study['study_url']))
																	<a href="{{ $study['study_url'] }}" title="{{ $study['study_title'] }}" target="_blank">
																		{{ str_limit($study['study_title'], $limit = 64, $end = '‥') }} (NCBI)
																	</a>
																@else
																	<span title="{{ $study['study_title'] }}">
																		{{ str_limit($study['study_title'], $limit = 64, $end = '‥') }}
																	</span>
																@endif

																 <em>{{ human_number($study['total_sequences']) }} sequences</em>
															</span>
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
					      </div>
					      <div class="modal-footer">
					        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
					      </div>
					    </div>
					  </div>
					</div>

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
				@endif
			</div>

			@if (empty($sample_list))
				<div class="no_results">
					<h2>No Results</h2>
					<p>Remove a filter or <a href="/samples">remove all filters</a> to return results.</p>
				</div>
			@endif

			@if (! empty($sample_list))
			{{ Form::open(array('url' => 'sequences', 'role' => 'form', 'method' => 'post', 'class' => 'sample_form')) }}
				<h3 class="pull-left">Individual Samples</h3>
				<p class="pull-right">
					{{ Form::submit('Browse sequences from selected samples →', array('class' => 'btn btn-primary browse-seq-data-button')) }}
				</p>
				
				<table class="table table-striped sample_list table-condensed">
					<thead> 
						<tr>
							<th>{{ Form::checkbox('select_all_rows', '', true, ['title' => 'Select all / Unselect all', 'class' => 'select_all_rows']) }}</th>
							<th>Repository</th>
							<th>@lang('short.lab_name')</th>
							<th>@lang('short.study_title')</th>
							<th>@lang('short.study_group_description')</th>
							<th>@lang('short.subject_id')</th>
							<th>@lang('short.tissue')</th>
							<th>@lang('short.cell_subset')</th>
							<th>@lang('short.cell_phenotype')</th>
							<th>Sequences</th>
							<th>@lang('short.sample_id')</th>
							<th>@lang('short.template_class')</th>
							<th>@lang('short.study_id')</th>
							<th>@lang('short.pub_ids')</th>
						</tr>
					</thead>
					<tbody>
						@foreach ($sample_list as $sample)
						<tr>
							<td>{{ Form::checkbox('ir_project_sample_id_list_' . $sample->rest_service_id . '[]', $sample->ir_project_sample_id, true) }}</td>
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
				<p class="pull-right">
				{{ Form::submit('Browse sequences from selected samples →', array('class' => 'btn btn-primary browse-seq-data-button')) }}
				</p>
			{{ Form::close() }}
			@endif

		</div>
	</div>
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
