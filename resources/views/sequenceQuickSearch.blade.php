@extends('template')

@section('title', 'Sequence Quick Search')

@section('content')
<div class="banner_title sequences">
	<h1>Sequence Quick Search</h1>
	<p class="sh1">Filter by sequence and sequence annotation features (e.g. Junction)</p>
</div>

<div class="container-fluid sequence_container">

	<div class="row loading_contents">
		<div class="col-md-2 filters">

			<h3 class="first">Filters</h3>

			{{ Form::open(array('url' => 'sequences-quick-search', 'role' => 'form', 'method' => 'post', 'class' => 'sequence_search show_reloading_message')) }}

				@foreach ($hidden_fields as $hf)
					<input type="hidden" name="{{$hf['name']}}" value="{{$hf['value']}}">
				@endforeach

				<div>
					{{ Form::label('junction_aa', __('short.junction_aa')) }}
					@include('help', ['id' => 'junction_aa'])
					{{ Form::text('junction_aa', '', array('class' => 'form-control', 'minlength' => '4', 'data-toggle' => 'tooltip', 'title' => 'Substring search (matches entire substring provided, minimum of 4 AA required). Takes several minutes if millions of sequences are found.', 'data-placement' => 'bottom')) }}

					@if (isset($conserved_aa_warning))
						<p class="bg-warning conserved_aa_warning">
							<span class="glyphicon glyphicon-info-sign"></span>
							To find all results across ADC/IEDB, remove conserved AAs: <code>{{ $junction_aa_without_conserved_aa }}</code>
						</p>
					@endif

					@if (isset($iedb_info) && $iedb_info)
						<div class="panel panel-primary iedb">
							<div class="panel-body">
								<p>
									<code>{{ $filter_fields['junction_aa'] }}</code>
									has known specificity to antigens from the following organisms:
								</p>
								<ul>
									@foreach ($iedb_organism_list as $i => $o)
										<li><span title="{{ $iedb_organism_list_extra[$i] }}">{{ $iedb_organism_list_short[$i] }}</span></li>
									@endforeach
								</ul>
								<p>
									<a href="https://www.iedb.org/result_v3.php" class="external" target="_blank">
										Find more information with a <br />"Receptor Search" at IEDB.org
									</a>
								</p>
							</div>
						</div>
					@endif
				</div>

				<div class="panel-group sqs_sample_filters" id="accordion" role="tablist" aria-multiselectable="true">
					<div class="panel panel-default">
						<div class="panel-heading" role="tab" id="headingThree">
							<h4 class="panel-title">
									Sample level filters
							</h4>
						</div>
						<div id="collapseThree" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="headingThree">
							<div class="panel-body">

							    <div class="form-group">
									{{ Form::label('organism', __('short.organism')) }}
									@include('help', ['id' => 'organism'])
									{{ Form::select('organism_id[]', $subject_organism_ontology_list, '', array('class' => 'form-control multiselect-ui', 'multiple' => 'multiple')) }}
								</div>

								<div class="form-group">
									{{ Form::label('cell_subset', __('short.cell_subset')) }}
									@include('help', ['id' => 'cell_subset'])
								    {{ Form::select('cell_subset_id[]', $cell_type_ontology_list, '', array('class' => 'form-control multiselect-ui', 'multiple' => 'multiple')) }}
								</div>

							</div>
						</div>
					</div>
				</div>

   				<div class="button_container">
					<p>
						{{ Form::submit('Apply filters →', array('class' => 'btn btn-primary search_samples')) }}
					</p>
   				</div>

			{{ Form::close() }}				
		</div>

		<div class="col-md-10">
			<div class="reloading_contents">

				<!-- Services which didn't respond -->
				@if ( ! empty($rest_service_list_no_response))
					<div class="alert alert-warning" role="alert">
						<p>Sorry, the statistics below are incomplete.</p>
						@if ( ! empty($rest_service_list_no_response_timeout))
							<p>These repositories did not return statistics before the Gateway time limit of {{ config('ireceptor.service_request_timeout') }} sec:</p>
							<ul>
								@foreach ($rest_service_list_no_response_timeout as $rs)
										<li>{{ $rs->display_name }}</li>
								@endforeach
							</ul>
							<p>For accurate statistics, try to reduce the size of the data you're exploring. You can also download the sequences and perform complex analyses offline.</p>
						@endif

						@if ( ! empty($rest_service_list_no_response_error))
							<p>An unexpected error occurred when querying the following repositories:</p>
							<ul>
								@foreach ($rest_service_list_no_response_error as $rs)
										<li>{{ $rs->display_name }}</li>
								@endforeach
							</ul>
							<p>Please try again later.</p>
						@endif
					</div>
				@endif


				<!-- Active filters -->
				@if ( ! empty($filter_fields))
					<div class="active_filters">
						<h3>Active filters</h3>
						
						@foreach($filter_fields as $filter_key => $filter_value)
							<a title= "@lang('short.' . $filter_key): {{ $filter_value }}" href="/sequences-quick-search?query_id={{ $query_id }}&amp;remove_filter={{ $filter_key }}" class="label label-primary">
								<span class="glyphicon glyphicon-remove" aria-hidden="true"></span>
								@lang('short.' . $filter_key): <span class="value">{{ $filter_value }}</span>
							</a>
						@endforeach

						<a href="/sequences-quick-search" class="remove_filters">
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

				@if (empty($sequence_list))
					<!-- No results -->
					<div class="no_results">
						<h2>No Results</h2>
						<p>
							Remove a filter
							@isset($no_filters_query_id)
								or <a href="/sequences?query_id={{ $no_filters_query_id }}">remove all filters</a>
							@endisset
							to return results.
						</p>
						<p>For more information, go to our <a href="http://ireceptor.org/platform/doc/faq" class="external" target="_blank"> FAQ (Frequently Asked Questions)</a></p>
					</div>
				@else
					<!-- Statistics -->
					<h3 class="{{ empty($filter_fields) ? 'first' : '' }}">Search results statistics</h3>
					<div class="statistics">
						<p>
							<strong class="summary">
								<span title="{{ number_format($total_filtered_objects) }}">
									{{ number_format($total_filtered_objects) }} sequences
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

						</p>
						
						<!-- repos/labs/studies details popup -->
						@include('rest_service_list', ['total_repositories' => $total_filtered_repositories, 'total_labs' => $total_filtered_labs, 'total_projects' => $total_filtered_studies, 'tab' => 'sequence'])

						<div class="charts">
							<div class="row">
								<div class="col-md-2 chart" data-chart-data="{!! object_to_json_for_html($charts_data['chart1']) !!}"></div>
								<div class="col-md-2 chart" data-chart-data="{!! object_to_json_for_html($charts_data['chart2']) !!}"></div>
								<div class="col-md-2 chart" data-chart-data="{!! object_to_json_for_html($charts_data['chart3']) !!}"></div>
								<div class="col-md-2 chart" data-chart-data="{!! object_to_json_for_html($charts_data['chart4']) !!}"></div>
								<div class="col-md-2 chart" data-chart-data="{!! object_to_json_for_html($charts_data['chart5']) !!}"></div>
								<div class="col-md-2 chart" data-chart-data="{!! object_to_json_for_html($charts_data['chart6']) !!}"></div>
							</div>
						</div>	
													
					</div>
				@endif


				@if (! empty($sequence_list))
					@if ($total_filtered_objects > config('ireceptor.sequences_download_limit'))
						<a href="/sequences-download" class="btn btn-primary pull-right download_sequences" disabled="disabled" role="button" data-container="body" data-toggle="tooltip" data-placement="top" title="Downloads of more than {{ number_format(config('ireceptor.sequences_download_limit')) }} sequences will be possible in the near future." data-trigger="hover" tabindex="0">
							<span class="glyphicon glyphicon-download-alt" aria-hidden="true"></span>
							<span class="text">Download all {{number_format($total_filtered_objects)}} sequences</span>
						</a>
					@else
						<a href="/sequences-download?query_id={{ $download_query_id }}&amp;n={{ $total_filtered_objects }}&amp;page=sequences-quick-search&amp;page_query_id={{ $query_id }}" class="btn btn-sequences pull-right download_sequences">
							<span class="glyphicon glyphicon-download-alt" aria-hidden="true"></span>
							<span class="text">Download all {{number_format($total_filtered_objects)}} sequences <strong>{{ $download_time_estimate ? '(will take up to ' . $download_time_estimate . ')' : ''}}</strong></span>
						</a>
					@endif

					<h3> 
						Individual Sequences

						<small class="sequence_count">
							1-{{ count($sequence_list) }}
							of
							<span title="{{ number_format($total_filtered_objects) }}">
								{{ human_number($total_filtered_objects) }}
							</span>
						</small>

						<a class="btn btn-xs" data-toggle="collapse" href="#column_selector" aria-expanded="false" aria-controls="column_selector" title="Edit Columns">
						  <span class="glyphicon glyphicon-edit" aria-hidden="true"></span>
						  Customize displayed columns
						</a>						
					</h3>
					
					<!-- table column selector -->
					@include('columnSelector')

					<!-- sequence data -->
					<table class="table table-striped table-condensed much_data table-bordered">
						<thead>
							<tr>
								@foreach ($field_list as $field)
									<th class="text-nowrap col_{{ $field['ir_id'] }} {{ in_array($field['ir_id'], $current_columns) ? '' : 'hidden' }}">
										@lang('short.' . $field['ir_id'])
										@include('help', ['id' => $field['ir_id']])
									</th>
								@endforeach
							</tr>
						</thead>
						<tbody>
							@foreach ($sequence_list as $s)
							<tr>
								@foreach ($field_list as $field)
									<td class="text-nowrap col_{{ $field['ir_id'] }} {{ in_array($field['ir_id'], $current_columns) ? '' : 'hidden' }}">
										@isset($s->{$field['ir_id']})
											@if($field['ir_id'] == 'functional')
												{{ $s->functional ? 'Yes' : 'No' }}											
											@elseif($field['ir_id'] == 'v_call' || $field['ir_id'] == 'v_call' || $field['ir_id'] == 'd_call' )
												{{ str_limit($s->{$field['ir_id']}, $limit = 30, $end = '‥') }}
											@else
												@if(is_object($s->{$field['ir_id']}))
													<span title="{{ json_encode($s->{$field['ir_id']}) }}">
														{{ json_encode($s->{$field['ir_id']}) }}												
													</span>
												@elseif (is_array($s->{$field['ir_id']}))
													<span title="{{ implode(', ', $s->{$field['ir_id']}) }}">
														{{ str_limit(implode(', ', $s->{$field['ir_id']}), $limit = 25, $end = '‥') }}									
													</span>			
												@else
													<span title="{{ $s->{$field['ir_id']} }}">
														@if (is_bool($s->{$field['ir_id']}))
															{{ $s->{$field['ir_id']} ? 'Yes' : 'No' }}
														@else
															{{ $s->{$field['ir_id']} }}
														@endif
													</span>
												@endif												
											@endif
										@endif
									</td>
								@endforeach
							</tr>
							@endforeach
						</tbody>
					</table>
				@endif
			</div>
		</div>
	</div>
</div>

@include('reloadingMessage')
@include('loadingMessage')

@stop
