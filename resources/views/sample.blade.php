@extends('template')

@section('title', 'Repertoire Metadata Search')

@section('content')

<div class="container-fluid sample_container">

	<h1>1. Repertoire Metadata Search</h1>
	<p class="sh1">Filter by study/subject/sample and choose repertoires to analyze relevant sequence data</p>

	<div class="row">
		<div class="col-md-2 filters">

			<h3 class="first">Filters</h3>

			{{ Form::open(array('url' => 'samples', 'role' => 'form', 'method' => 'post', 'class' => 'sample_search show_reloading_message')) }}
				<input type="hidden" name="project_id_list" />
				<input type="hidden" name="cols" value="{{ $current_columns_str }}">

			    <div class="form-group full_text_search">
					{{ Form::label('full_text_search', __('short.full_text_search')) }}
					@include('help', ['id' => 'full_text_search'])
					{{ Form::text('full_text_search', '', array('class' => 'form-control')) }}
				</div>

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
									{{ Form::label('study_type', __('short.study_type')) }}
									@include('help', ['id' => 'study_type'])
									{{ Form::select('study_type[]', $study_type_list, '', array('class' => 'form-control multiselect-ui', 'multiple' => 'multiple')) }}
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
									{{ Form::submit('Apply filters →', array('class' => 'btn btn-primary search_samples')) }}
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
									{{ Form::select('organism', $subject_organism_list, '', array('class' => 'form-control')) }}
								</div>

								<div class="form-group">
									{{ Form::label('sex', __('short.sex')) }}
									@include('help', ['id' => 'sex'])
									{{ Form::select('sex[]', $subject_gender_list, '', array('class' => 'form-control multiselect-ui', 'multiple' => 'multiple')) }}
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
									@include('help', ['id' => 'age'])
									<div class="row">
										<div class="col-md-6">
											{{ Form::label('ir_subject_age_min', __('short.ir_subject_age_min')) }}
											@include('help', ['id' => 'ir_subject_age_min'])
											{{ Form::text('ir_subject_age_min', '', array('class' => 'form-control', 'placeholder' => '')) }}
										</div>
										<div class="col-md-6">
											{{ Form::label('ir_subject_age_max', __('short.ir_subject_age_max')) }}
											@include('help', ['id' => 'ir_subject_age_max'])
											{{ Form::text('ir_subject_age_max', '', array('class' => 'form-control', 'placeholder' => '')) }}
										</div>
									</div>
								</div>

								<p class="button_container">
									{{ Form::submit('Apply filters →', array('class' => 'btn btn-primary search_samples')) }}
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
								    {{ Form::select('template_class[]', $dna_type_list, '', array('class' => 'form-control multiselect-ui', 'multiple' => 'multiple')) }}
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
									{{ Form::submit('Apply filters →', array('class' => 'btn btn-primary search_samples')) }}
								</p>

							</div>
						</div>
					</div>
				</div>
			    
			{{ Form::close() }}
		</div>

		<div class="col-md-10">

			@include('finishingLoadingMessage', ['total_filtered_samples' => $total_filtered_samples, 'total_filtered_repositories' => $total_filtered_repositories])

			<div class="reloading_contents hidden">
				<!-- Active filters -->
				@if ( ! empty($filter_fields))
					<div class="active_filters">
						<h3>Active filters</h3>
						@foreach($filter_fields as $filter_key => $filter_value)
							<a title= "@lang('short.' . $filter_key): {{$filter_value}}" href="/samples?query_id={{$sample_query_id}}&amp;remove_filter={{ $filter_key }}" class="label label-primary">
								<span class="glyphicon glyphicon-remove" aria-hidden="true"></span>
								@lang('short.' . $filter_key)
							</a>
						@endforeach
						<a href="/samples?query_id={{ $query_id }}&amp;remove_filter=all" class="remove_filters">
							Remove all filters
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
						<p>For more information, go to our <a href="http://ireceptor.org/platform/doc/faq" class="external" target="_blank"> FAQ (Frequently Asked Questions)</a></p>			
					</div>
				@else
					<!-- Statistics -->
					<h3 class="{{ empty($filter_fields) ? 'first' : '' }}">Search results statistics</h3>
					<div class="statistics">
						<p>
							<strong>
								<span title="{{ number_format($total_filtered_sequences) }}">
									{{ human_number($total_filtered_sequences) }} sequences
								</span>
								({{ $total_filtered_samples }} {{ str_plural('repertoire', $total_filtered_samples)}})
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

						<div id="charts" class="charts">
							<div class="row">
								<div class="col-md-2 chart" id="chart1"></div>
								<div class="col-md-2 chart" id="chart2"></div>
								<div class="col-md-2 chart" id="chart3"></div>
								<div class="col-md-2 chart" id="chart4"></div>
								<div class="col-md-2 chart" id="chart5"></div>
								<div class="col-md-2 chart" id="chart6"></div>
							</div>
						</div>								
					</div>
				@endif


				@if (! empty($sample_list))

				<!-- table column selector -->
				<div class="collapse" id="column_selector">
					<div class="panel panel-default">
						<div class="panel-heading">
							<button class="btn btn-primary btn-xs pull-right" data-toggle="collapse" href="#column_selector" aria-expanded="false" aria-controls="column_selector">
					  			Done
							</button>
							<h4 class="panel-title">Edit Individual Repertoires Columns</h4>
						</div>
				  		<div class="panel-body">
							<form class="column_selector">
								@foreach ($field_list_grouped as $field_group)
									<h5>{{ $field_group['name'] }}</h5>
									@foreach ($field_group['fields'] as $field)
										<div class="checkbox">
											<label>
												<input name="table_columns" class="{{ $field['ir_id'] }}" data-id="{{ $field['ir_id'] }}" type="checkbox" value="{{'col_' . $field['ir_id']}}" {{ in_array($field['ir_id'], $current_columns) ? 'checked="checked"' : '' }}/>
												 @include('help', ['id' => $field['ir_id']])
												 @lang('short.' . $field['ir_id'])
											</label>
										</div>		
									@endforeach
								@endforeach
							</form>
				  		</div>
					</div>
				</div>

				{{ Form::open(array('url' => 'sequences', 'role' => 'form', 'method' => 'post', 'class' => 'sample_form show_loading_message')) }}

					<h3>Individual Repertoires</h3>
					<p class="table_info">
						<span class="nb_selected_samples">{{ count($sample_list) }}</span> repertoires selected
						<a class="unselect_all_samples" href="#">Unselect All</a>
						<a class="select_all_samples" href="#">Select All</a>
						{{ Form::submit('Browse sequences from selected repertoires →', array('class' => 'btn btn-primary browse_sequences browse-seq-data-button button_to_enable_on_load', 'disabled' => 'disabled')) }}
					</p>
					
					<!-- sample data -->
					<table class="table table-striped sample_list table-condensed much_data table-bordered">
						<thead> 
							<tr>
								<th class="checkbox_cell">
									<a class="btn btn-primary btn-xs" data-toggle="collapse" href="#column_selector" aria-expanded="false" aria-controls="column_selector" title="Edit Columns">
									  <span class="glyphicon glyphicon-edit" aria-hidden="true"></span>
									</a>
								</th>

								@foreach ($field_list as $field)
									<th class="text-nowrap col_{{ $field['ir_id'] }} {{ in_array($field['ir_id'], $current_columns) ? '' : 'hidden' }}">
										@lang('short.' . $field['ir_id'])
										@include('help', ['id' => $field['ir_id']])
									</th>
								@endforeach
							</tr>
						</thead>
						<tbody>
							@foreach ($sample_list as $sample)
							<tr>
								<td class="checkbox_cell">
									<input type="checkbox" name="{{ 'ir_project_sample_id_list_' . $sample->real_rest_service_id . '[]' }}" value="{{ $sample->repertoire_id }}" checked="checked" autocomplete="off"/>
								</td>

								@foreach ($field_list as $field)
									<td class="text-nowrap col_{{ $field['ir_id'] }} {{ in_array($field['ir_id'], $current_columns) ? '' : 'hidden' }}">
										@isset($sample->{$field['ir_id']})
											@if($field['ir_id'] == 'ir_sequence_count')
												@if ($sample->ir_sequence_count > 0)
													<a href="sequences?ir_project_sample_id_list_{{ $sample->real_rest_service_id }}[]={{ $sample->repertoire_id }}@if($sample_query_id != '')&amp;sample_query_id={{ $sample_query_id }}@endif">
														<span class="label label-primary">{{number_format($sample->ir_sequence_count, 0 ,'.' ,',') }}</span>
													</a>
												@endif
											@elseif($field['ir_id'] == 'study_id')
												@isset($sample->ncbi_url)
													<a href="{{ $sample->ncbi_url }}" title="{{ $sample->ncbi_url }}" target="_blank">
														{{ str_limit($sample->study_id, $limit = 20, $end = '‥') }}
													</a>
												@else
													<span title="{{ $sample->{$field['ir_id']} }}">
														{{ str_limit($sample->{$field['ir_id']}, $limit = 20, $end = '‥') }}
													</span>						
												@endisset
											@elseif($field['ir_id'] == 'study_title')
												@isset($sample->study_url)
													<a href="{{ $sample->study_url }}" title="{{ $sample->study_title }}" target="_blank">
														{{ str_limit($sample->study_title, $limit = 20, $end = '‥') }}
													</a>
												@else
													<span title="{{ $sample->study_title }}">
														{{ str_limit($sample->study_title, $limit = 20, $end = '‥') }}
													</span>							
												@endisset
											@elseif($field['ir_id'] == 'pub_ids')
												@isset($sample->study_url)
													<a href="{{ $sample->study_url }}" title="{{ $sample->study_url }}" target="_blank">
														{{ str_limit(remove_url_prefix($sample->study_url), $limit = 25, $end = '‥') }}
													</a>
												@else
													<span title="{{ $sample->{$field['ir_id']} }}">
														{{ $sample->{$field['ir_id']} }}
													</span>							
												@endisset
											@else
												@if (is_bool($sample->{$field['ir_id']}))
													{{ $sample->{$field['ir_id']} ? 'Yes' : 'No' }}
												@else
													@if (is_object($sample->{$field['ir_id']}))
														object...														
													@else
														<span title="{{ $sample->{$field['ir_id']} }}">
															{{ str_limit($sample->{$field['ir_id']}, $limit = 20, $end = '‥') }}
														</span>
													@endif
												@endif
										@endif
										@endif
									</td>
								@endforeach
							</tr>
							@endforeach
						</tbody>
					</table>

					<input type="hidden" name="project_id_list" />
					<input type="hidden" name="sample_query_id" value="{{ $sample_query_id }}" />
					<p class="pull-right">
					{{ Form::submit('Browse sequences from selected repertoires →', array('class' => 'btn btn-primary browse-seq-data-button', 'disabled' => 'disabled')) }}
					</p>
				{{ Form::close() }}
				@endif

			</div>
		</div>
	</div>
</div>

@include('reloadingMessage')
@include('loadingMessage')

<script>
	var graphFields = [
	        "study_description", 
	        "organism",
	        "disease_state_sample", 
	        "tissue",
	        "cell_subset", 
	        "template_class"
	    ];
	var graphNames = [
	        "@lang('short.study_description')",
	        "@lang('short.organism')", 
	        "@lang('short.disease_state_sample')",
	        "@lang('short.tissue')", 
	        "@lang('short.cell_subset')", 
	        "@lang('short.template_class')"
	    ];

	var graphInternalLabels = true;
	var graphCountField = "ir_sequence_count";
	var graphData = {!! $sample_list_json !!};
</script>
@stop
