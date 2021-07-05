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
				<input type="hidden" name="sort_column" value="{{ $sort_column }}">
				<input type="hidden" name="sort_order" value="{{ $sort_order }}">

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
									{{ Form::select('organism[]', $subject_organism_list, '', array('class' => 'form-control multiselect-ui', 'multiple' => 'multiple')) }}
								</div>

								<div class="form-group">
									{{ Form::label('sex', __('short.sex')) }}
									@include('help', ['id' => 'sex'])
									{{ Form::select('sex[]', $subject_gender_list, '', array('class' => 'form-control multiselect-ui', 'multiple' => 'multiple')) }}
								</div>

							    <div class="form-group">
									{{ Form::label('ethnicity', __('short.ethnicity')) }}
									@include('help', ['id' => 'ethnicity'])
									{{ Form::select('ethnicity[]', $subject_ethnicity_list, '', array('class' => 'form-control multiselect-ui', 'multiple' => 'multiple')) }}
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

							    <div class="form-group">
									{{ Form::label('disease_diagnosis', __('short.disease_diagnosis')) }}
									@include('help', ['id' => 'disease_diagnosis'])
									{{ Form::select('disease_diagnosis[]', $subject_disease_diagnosis_list, '', array('class' => 'form-control multiselect-ui', 'multiple' => 'multiple')) }}
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
									{{ Form::label('pcr_target_locus', __('short.pcr_target_locus')) }}
									@include('help', ['id' => 'pcr_target_locus'])
								    {{ Form::select('pcr_target_locus[]', $pcr_target_locus_list, '', array('class' => 'form-control multiselect-ui', 'multiple' => 'multiple')) }}
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

					<div class="panel panel-default">
						<div class="panel-heading" role="tab" id="headingFour">
							<h4 class="panel-title">
								<a class="{{ in_array('3', $open_filter_panel_list) ? '' : 'collapsed' }}" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
									More filters
								</a>
							</h4>
						</div>
						<div id="collapseFour" class="panel-collapse collapse {{ in_array('3', $open_filter_panel_list) ? 'in' : '' }}" role="tabpanel" aria-labelledby="headingFour">
							<div class="panel-body">
								<div class="extra_fields {{ empty($extra_params) ? 'hidden' : '' }}">
									<div class="extra_fields_list">
										@foreach($extra_params as $param)
											<div class="form-group">
												{{ Form::label($param, __('short.' . $param)) }}

												@include('help', ['id' => $param])

												<span class="remove_field" role="button" data-container="body" title="Remove filter">
													<span class="glyphicon glyphicon-remove"></span>
												</span>

												{{ Form::text($param, '', array('class' => 'form-control')) }}
											</div>										
										@endforeach
									</div>

									<p class="button_container">
										{{ Form::submit('Apply filters →', array('class' => 'btn btn-primary search_samples')) }}
									</p>
									<hr>								
								</div>

								<div class="add_field">
									<div class="form-group">
										<label for="extra_field">Add a filter</label>
										{{ Form::select('extra_field', $extra_fields, '', array('class' => 'form-control'), $extra_fields_options_attributes) }}
									</div>

									<p class="button_container">
										<button type="button" class="btn btn-default" aria-label="Add filter">
											<span class="glyphicon glyphicon-plus" aria-hidden="true"></span> <span class="text">Add filter</span>
										</button>
									</p>
								</div>				 	
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
			

				@if (empty($samples_with_sequences) && empty($samples_with_clones))
					<div class="no_results">
						<h2>No Results</h2>
						<p>Remove a filter or <a href="/samples">remove all filters</a> to return results.</p>
						<p>For more information, go to our <a href="http://ireceptor.org/platform/doc/faq" class="external" target="_blank"> FAQ (Frequently Asked Questions)</a></p>			
					</div>
				@else
					<!-- Nav tabs -->
					<ul class="nav nav-tabs" role="tablist">
						<li role="presentation" class="active"><a href="#repertoireSequenceSearchResults" aria-controls="home" role="tab" data-toggle="tab">Sequence Search Results</a></li>
						<li role="presentation"><a href="#repertoireCloneSearchResults" aria-controls="profile" role="tab" data-toggle="tab">Clone Search Results</a></li>
					</ul>

					<!-- Tab panes -->
					<div class="tab-content">
						<div role="tabpanel" class="tab-pane active" id="repertoireSequenceSearchResults">
							<!-- Statistics -->
							<h3 class="{{ empty($filter_fields) ? 'first' : '' }}">Statistics</h3>
							<div class="statistics">
								<p>
									<strong>
										<span title="{{ number_format($total_filtered_sequences) }}">
											{{ number_format($total_filtered_sequences) }} sequences
										</span>
										({{ $total_filtered_samples }} {{ str_plural('repertoire', $total_filtered_samples)}})
									</strong>
									returned from
				
									<a href="#" class="toggle_modal_rest_service_list_folded">
										{{ $total_filtered_repositories }} remote {{ str_plural('repository', $total_filtered_repositories)}},</a>
									<a href="#" class="toggle_modal_rest_service_list_expanded">
										{{ $total_filtered_labs }} research {{ str_plural('lab', $total_filtered_labs)}} and
										{{ $total_filtered_studies }} {{ str_plural('study', $total_filtered_studies)}}.
									</a>

									@if ( ($rs_list_no_response_str != '') || ($rs_list_sequence_count_error_str != ''))
										<a role="button" class="missing_data" data-container="body" data-toggle="popover_form_field" data-placement="right" title="Incomplete data" data-content="{{ $rs_list_no_response_str }}{{ $rs_list_sequence_count_error_str }}" data-trigger="hover" tabindex="0">
											<span class="glyphicon glyphicon-exclamation-sign"></span>								
										</a>
									@endif

								</p>

								<!-- repos/labs/studies details popup -->
								@include('rest_service_list', ['total_repositories' => $total_filtered_repositories, 'total_labs' => $total_filtered_labs, 'total_projects' => $total_filtered_studies])

								<div class="charts">
									<div class="row">
										<div class="col-md-2 chart" data-chart-data="{!! object_to_json_for_html($sequence_charts_data['study_type']) !!}"></div>
										<div class="col-md-2 chart" data-chart-data="{!! object_to_json_for_html($sequence_charts_data['organism']) !!}"></div>
										<div class="col-md-2 chart" data-chart-data="{!! object_to_json_for_html($sequence_charts_data['disease_diagnosis']) !!}"></div>
										<div class="col-md-2 chart" data-chart-data="{!! object_to_json_for_html($sequence_charts_data['tissue']) !!}"></div>
										<div class="col-md-2 chart" data-chart-data="{!! object_to_json_for_html($sequence_charts_data['pcr_target_locus']) !!}"></div>
										<div class="col-md-2 chart" data-chart-data="{!! object_to_json_for_html($sequence_charts_data['template_class']) !!}"></div>
									</div>
								</div>

							</div>

							<!-- table column selector -->
							<div class="collapse" id="column_selector">
								<div class="panel panel-default">
									<div class="panel-heading">
										<h4 class="panel-title">
											Customize displayed columns
											<button class="btn btn-primary btn-xs" data-toggle="collapse" href="#column_selector" aria-expanded="false" aria-controls="column_selector">
												<span class="glyphicon glyphicon-remove" aria-hidden="true"></span>
									  			Close
											</button>
										</h4>
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


							<div class="row">
								<div class="col-md-6">
									<h3>
										Individual Repertoires

										<small>
											{{ $page_first_element_index }}-{{ $page_last_element_index }} of {{ $nb_samples }}
										</small>

										<a class="btn btn-xs" data-toggle="collapse" href="#column_selector" aria-expanded="false" aria-controls="column_selector" title="Edit Columns">
										  <span class="glyphicon glyphicon-edit" aria-hidden="true"></span>
										  Customize displayed columns
										</a>
									</h3>
								</div>
								<div class="col-md-6 repertoires_button_container">
									<a role="button" class="btn btn-primary browse_sequences browse-seq-data-button button_to_enable_on_load"  href="/sequences?query_id={{ $sequences_query_id }}">
										Browse sequences from {{ $nb_samples }} repertoires →
									</a>
								
									<a href="/samples/tsv?query_id={{ $sample_query_id }}" class="btn btn-default download_repertoires" type="button" title="Download repertoire metadata search results as TSV">
										<span class="glyphicon glyphicon-download-alt" aria-hidden="true"></span>
										<span class="text">TSV</span>
									</a>

									<a href="/samples/json?query_id={{ $sample_query_id }}" class="btn btn-default download_repertoires" type="button" title="Download repertoire metadata search results as JSON">
										<span class="glyphicon glyphicon-download-alt" aria-hidden="true"></span>
										<span class="text">JSON</span>
									</a>							
								</div>
							</div>
							
							<!-- sample data -->
							<table class="table table-striped sample_list table-condensed much_data table-bordered sortable">
								<thead> 
									<tr>
										<th class="stats">Stats</th>
										@foreach ($field_list as $field)

											{{-- skip clones column --}}
											@if ($field['ir_id'] == 'ir_clone_count')
												@continue
											@endif

											<th class="sort text-nowrap col_{{ $field['ir_id'] }} {{ in_array($field['ir_id'], $current_columns) ? '' : 'hidden' }}">
													@if ($field['ir_id'] == $sort_column)
														@if ($sort_order == 'asc')
															<a class="sort_column" role="button" href="/samples?query_id={{ $sample_query_id }}&amp;sort_column={{ $field['ir_id'] }}&amp;sort_order=desc">
																@lang('short.' . $field['ir_id'])
																@include('help', ['id' => $field['ir_id']])
																<span class="glyphicon sort_icon sorted_asc"></span>
															</a>
														@else
															<a class="sort_column" role="button" href="/samples?query_id={{ $sample_query_id }}&amp;sort_column={{ $field['ir_id'] }}&amp;sort_order=asc">
																@lang('short.' . $field['ir_id'])
																@include('help', ['id' => $field['ir_id']])
																<span class="glyphicon sort_icon sorted_desc"></span>
															</a>
														@endif
													@else
														<a class="sort_column" role="button" href="/samples?query_id={{ $sample_query_id }}&amp;sort_column={{ $field['ir_id'] }}&amp;sort_order=asc">
															@lang('short.' . $field['ir_id'])
															@include('help', ['id' => $field['ir_id']])
															<span class="glyphicon sort_icon"></span>
														</a>
													@endif
											</th>
										@endforeach
									</tr>
								</thead>
								<tbody>
									@foreach ($samples_with_sequences as $sample)
									<tr>
										<td class="stats">
											@if(isset($sample->stats) && $sample->stats)
												<a href="#modal_stats" data-url="/samples/stats/{{ $sample->real_rest_service_id }}/{{ $sample->repertoire_id }}" data-repertoire-name="{{ $sample->subject_id }} - {{ $sample->sample_id }} - {{ $sample->pcr_target_locus }}" data-toggle="modal" data-target="#statsModal">
													<span class="label label-primary">
														<span class="glyphicon glyphicon-stats" aria-hidden="true"></span>
													</span>
												</a>
												@if(isset($sample->show_stats_notification))
													<div class="stats_notification_container">
														<div class="tooltip left in stats_notification" style="display: block;">
															<div class="tooltip-arrow" style="top: 50%;"></div>
															<div class="tooltip-inner">Repertoire statistics<br>are now available.</div>
														</div>
													</div>
												@endif
											@endif						
										</td>
										@foreach ($field_list as $field)
											<td class="text-nowrap col_{{ $field['ir_id'] }} {{ in_array($field['ir_id'], $current_columns) ? '' : 'hidden' }}">
												@isset($sample->{$field['ir_id']})
													@if($field['ir_id'] == 'ir_sequence_count')
														@if ($sample->ir_sequence_count > 0)
															<a class="number" href="sequences?ir_project_sample_id_list_{{ $sample->real_rest_service_id }}[]={{ $sample->repertoire_id }}@if($sample_query_id != '')&amp;sample_query_id={{ $sample_query_id }}@endif">
																{{number_format($sample->ir_sequence_count, 0 ,'.' ,',') }}
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
													@elseif($field['ir_id'] == 'repertoire_id')
														{{ $sample->{$field['ir_id']} }}
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
																<span title="{{ json_encode($sample->{$field['ir_id']}) }}">
																	{{ str_limit(json_encode($sample->{$field['ir_id']}), $limit = 20, $end = '‥') }}									
																</span>			
															@elseif (is_array($sample->{$field['ir_id']}))
																<span title="{{ implode(', ', $sample->{$field['ir_id']}) }}">
																	{{ str_limit(implode(', ', $sample->{$field['ir_id']}), $limit = 25, $end = '‥') }}									
																</span>			
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

							<div class="row">
								<div class="col-md-6">
									@if ($nb_pages > 1)
										<nav aria-label="Individual Repertoires">
											<ul class="pagination">
												@for ($i = 1; $i <= $nb_pages; $i++)
													@if ($i == $page)
														<li class="active">
															<span>{{ $i }} <span class="sr-only">(current)</span></span>
													    </li>										
													@else
													<li>
														<a href="/samples?query_id={{$sample_query_id}}&amp;page={{ $i }}">
															{{ $i }}
														</a>
													</li>
													@endif
												@endfor
											</ul>
										</nav>
									@endif
								</div>

								<div class="col-md-6 repertoires_button_container">
									<a role="button" class="btn btn-primary browse_sequences browse-seq-data-button button_to_enable_on_load"  href="/sequences?query_id={{ $sequences_query_id }}">
										Browse sequences from {{ $nb_samples }} repertoires →
									</a>
								</div>
							</div>

						</div>

						<div role="tabpanel" class="tab-pane" id="repertoireCloneSearchResults">
							<!-- Statistics -->
							<h3 class="{{ empty($filter_fields) ? 'first' : '' }}">Statistics</h3>
							<div class="statistics">
								<p>
									<strong>
										<span title="{{ number_format($total_filtered_clones) }}">
											{{ number_format($total_filtered_clones) }} clones
										</span>
										({{ $total_filtered_samples }} {{ str_plural('repertoire', $total_filtered_samples)}})
									</strong>
									returned from
				
									<a href="#" class="toggle_modal_rest_service_list_folded">
										{{ $total_filtered_repositories }} remote {{ str_plural('repository', $total_filtered_repositories)}},</a>
									<a href="#" class="toggle_modal_rest_service_list_expanded">
										{{ $total_filtered_labs }} research {{ str_plural('lab', $total_filtered_labs)}} and
										{{ $total_filtered_studies }} {{ str_plural('study', $total_filtered_studies)}}.
									</a>

									@if ( ($rs_list_no_response_str != '') || ($rs_list_sequence_count_error_str != ''))
										<a role="button" class="missing_data" data-container="body" data-toggle="popover_form_field" data-placement="right" title="Incomplete data" data-content="{{ $rs_list_no_response_str }}{{ $rs_list_sequence_count_error_str }}" data-trigger="hover" tabindex="0">
											<span class="glyphicon glyphicon-exclamation-sign"></span>								
										</a>
									@endif

								</p>

								<!-- repos/labs/studies details popup -->
								@include('rest_service_list', ['total_repositories' => $total_filtered_repositories, 'total_labs' => $total_filtered_labs, 'total_projects' => $total_filtered_studies])

								<div class="charts">
									<div class="row">
										<div class="col-md-2 chart" data-chart-data="{!! object_to_json_for_html($clone_charts_data['study_type']) !!}"></div>
										<div class="col-md-2 chart" data-chart-data="{!! object_to_json_for_html($clone_charts_data['organism']) !!}"></div>
										<div class="col-md-2 chart" data-chart-data="{!! object_to_json_for_html($clone_charts_data['disease_diagnosis']) !!}"></div>
										<div class="col-md-2 chart" data-chart-data="{!! object_to_json_for_html($clone_charts_data['tissue']) !!}"></div>
										<div class="col-md-2 chart" data-chart-data="{!! object_to_json_for_html($clone_charts_data['pcr_target_locus']) !!}"></div>
										<div class="col-md-2 chart" data-chart-data="{!! object_to_json_for_html($clone_charts_data['template_class']) !!}"></div>
									</div>
								</div>
								
							</div>

							<!-- table column selector -->
							<div class="collapse" id="column_selector">
								<div class="panel panel-default">
									<div class="panel-heading">
										<h4 class="panel-title">
											Customize displayed columns
											<button class="btn btn-primary btn-xs" data-toggle="collapse" href="#column_selector" aria-expanded="false" aria-controls="column_selector">
												<span class="glyphicon glyphicon-remove" aria-hidden="true"></span>
									  			Close
											</button>
										</h4>
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


							<div class="row">
								<div class="col-md-6">
									<h3>
										Individual Repertoires

										<small>
											{{ $page_first_element_index }}-{{ $page_last_element_index }} of {{ $nb_samples }}
										</small>

										<a class="btn btn-xs" data-toggle="collapse" href="#column_selector" aria-expanded="false" aria-controls="column_selector" title="Edit Columns">
										  <span class="glyphicon glyphicon-edit" aria-hidden="true"></span>
										  Customize displayed columns
										</a>
									</h3>
								</div>
								<div class="col-md-6 repertoires_button_container">
									<a role="button" class="btn btn-primary browse_sequences browse-seq-data-button button_to_enable_on_load"  href="/sequences?query_id={{ $sequences_query_id }}">
										Browse clones from {{ $nb_samples }} repertoires →
									</a>
								
									<a href="/samples/tsv?query_id={{ $sample_query_id }}" class="btn btn-default download_repertoires" type="button" title="Download repertoire metadata search results as TSV">
										<span class="glyphicon glyphicon-download-alt" aria-hidden="true"></span>
										<span class="text">TSV</span>
									</a>

									<a href="/samples/json?query_id={{ $sample_query_id }}" class="btn btn-default download_repertoires" type="button" title="Download repertoire metadata search results as JSON">
										<span class="glyphicon glyphicon-download-alt" aria-hidden="true"></span>
										<span class="text">JSON</span>
									</a>							
								</div>
							</div>
							
							<!-- sample data -->
							<table class="table table-striped sample_list table-condensed much_data table-bordered sortable">
								<thead> 
									<tr>
										<th class="stats">Stats</th>
										@foreach ($field_list as $field)
											{{-- skip sequence column --}}
											@if ($field['ir_id'] == 'ir_sequence_count')
												@continue
											@endif
											<th class="sort text-nowrap col_{{ $field['ir_id'] }} {{ in_array($field['ir_id'], $current_columns) ? '' : 'hidden' }}">
													@if ($field['ir_id'] == $sort_column)
														@if ($sort_order == 'asc')
															<a class="sort_column" role="button" href="/samples?query_id={{ $sample_query_id }}&amp;sort_column={{ $field['ir_id'] }}&amp;sort_order=desc">
																@lang('short.' . $field['ir_id'])
																@include('help', ['id' => $field['ir_id']])
																<span class="glyphicon sort_icon sorted_asc"></span>
															</a>
														@else
															<a class="sort_column" role="button" href="/samples?query_id={{ $sample_query_id }}&amp;sort_column={{ $field['ir_id'] }}&amp;sort_order=asc">
																@lang('short.' . $field['ir_id'])
																@include('help', ['id' => $field['ir_id']])
																<span class="glyphicon sort_icon sorted_desc"></span>
															</a>
														@endif
													@else
														<a class="sort_column" role="button" href="/samples?query_id={{ $sample_query_id }}&amp;sort_column={{ $field['ir_id'] }}&amp;sort_order=asc">
															@lang('short.' . $field['ir_id'])
															@include('help', ['id' => $field['ir_id']])
															<span class="glyphicon sort_icon"></span>
														</a>
													@endif
											</th>
										@endforeach
									</tr>
								</thead>
								<tbody>
									@foreach ($samples_with_clones as $sample)
									<tr>
										<td class="stats">
											@if(isset($sample->stats) && $sample->stats)
												<a href="#modal_stats" data-url="/samples/stats/{{ $sample->real_rest_service_id }}/{{ $sample->repertoire_id }}" data-repertoire-name="{{ $sample->subject_id }} - {{ $sample->sample_id }} - {{ $sample->pcr_target_locus }}" data-toggle="modal" data-target="#statsModal">
													<span class="label label-primary">
														<span class="glyphicon glyphicon-stats" aria-hidden="true"></span>
													</span>
												</a>
												@if(isset($sample->show_stats_notification))
													<div class="stats_notification_container">
														<div class="tooltip left in stats_notification" style="display: block;">
															<div class="tooltip-arrow" style="top: 50%;"></div>
															<div class="tooltip-inner">Repertoire statistics<br>are now available.</div>
														</div>
													</div>
												@endif
											@endif						
										</td>
										@foreach ($field_list as $field)
											<td class="text-nowrap col_{{ $field['ir_id'] }} {{ in_array($field['ir_id'], $current_columns) ? '' : 'hidden' }}">
												@isset($sample->{$field['ir_id']})
													@if($field['ir_id'] == 'ir_clone_count')
														@if ($sample->ir_clone_count > 0)
															<a class="number" href="clones?ir_project_sample_id_list_{{ $sample->real_rest_service_id }}[]={{ $sample->repertoire_id }}@if($sample_query_id != '')&amp;sample_query_id={{ $sample_query_id }}@endif">
																{{number_format($sample->ir_clone_count, 0 ,'.' ,',') }}
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
													@elseif($field['ir_id'] == 'repertoire_id')
														{{ $sample->{$field['ir_id']} }}
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
																<span title="{{ json_encode($sample->{$field['ir_id']}) }}">
																	{{ str_limit(json_encode($sample->{$field['ir_id']}), $limit = 20, $end = '‥') }}									
																</span>			
															@elseif (is_array($sample->{$field['ir_id']}))
																<span title="{{ implode(', ', $sample->{$field['ir_id']}) }}">
																	{{ str_limit(implode(', ', $sample->{$field['ir_id']}), $limit = 25, $end = '‥') }}									
																</span>			
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

							<div class="row">
								<div class="col-md-6">
									@if ($nb_pages > 1)
										<nav aria-label="Individual Repertoires">
											<ul class="pagination">
												@for ($i = 1; $i <= $nb_pages; $i++)
													@if ($i == $page)
														<li class="active">
															<span>{{ $i }} <span class="sr-only">(current)</span></span>
													    </li>										
													@else
													<li>
														<a href="/samples?query_id={{$sample_query_id}}&amp;page={{ $i }}">
															{{ $i }}
														</a>
													</li>
													@endif
												@endfor
											</ul>
										</nav>
									@endif
								</div>

								<div class="col-md-6 repertoires_button_container">
									<a role="button" class="btn btn-primary browse_sequences browse-seq-data-button button_to_enable_on_load"  href="/sequences?query_id={{ $sequences_query_id }}">
										Browse clones from {{ $nb_samples }} repertoires →
									</a>
								</div>
							</div>
						</div>
					</div>






				<!-- Repertoire Statistics Modal -->
				<div class="modal fade" id="statsModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
					<div class="modal-dialog" role="document">
						<div class="modal-content">
							<div class="modal-header">
								<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
								<h4 class="modal-title" id="statsModalLabel">Modal title</h4>
							</div>
							<div class="modal-body" id="">
								<!-- Nav tabs -->
								<ul class="nav nav-tabs" role="tablist">
									<li role="presentation" class="active"><a href="#stats_vgene" data-stat="v_gene_usage" role="tab" data-toggle="tab">V-gene</a></li>
									<li role="presentation"><a href="#stats_dgene" data-stat="d_gene_usage"role="tab" data-toggle="tab">D-gene</a></li>
									<li role="presentation"><a href="#stats_jgene" data-stat="j_gene_usage" role="tab" data-toggle="tab">J-gene</a></li>
									<li role="presentation"><a href="#stats_junction_length" data-stat="junction_length_stats" role="tab" data-toggle="tab">Junction Length (AA)</a></li>
								</ul>

								<!-- Tab panes -->
								<div class="tab-content">
									<div role="tabpanel" class="tab-pane active" id="stats_vgene"><p>Loading V-gene graph...</p></div>
									<div role="tabpanel" class="tab-pane" id="stats_dgene"><p>Loading D-gene graph...</p></div>
									<div role="tabpanel" class="tab-pane" id="stats_jgene"><p>Loading J-gene graph...</p></div>
									<div role="tabpanel" class="tab-pane" id="stats_junction_length"><p>Loading Junction Length graph...</p></div>
								</div>
							</div>

							<div class="modal-footer sample_stats_info">
								<p class="loading">Loading repertoire metadata...</p>
							</div>
							<div class="modal-footer">
								<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
							</div>
						</div>
					</div>
				</div>
				
				@endif

			</div>
		</div>
	</div>
</div>

@include('reloadingMessage')
@include('loadingMessage')

@stop
