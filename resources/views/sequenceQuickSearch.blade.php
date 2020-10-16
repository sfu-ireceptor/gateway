@extends('template')

@section('title', 'Sequence Quick Search')

@section('content')
<div class="container-fluid sequence_container">

	<h1>Sequence Quick Search</h1>
	<p class="sh1">Filter by sequence and sequence annotation features (e.g. Junction)</p>

	<div class="row loading_contents">
		<div class="col-md-2 filters">

			<h3 class="first">Filters</h3>

			{{ Form::open(array('url' => 'sequences-quick-search', 'role' => 'form', 'method' => 'post', 'class' => 'sequence_search show_reloading_message')) }}

				@foreach ($hidden_fields as $hf)
					<input type="hidden" name="{{$hf['name']}}" value="{{$hf['value']}}">
				@endforeach

					
				<div class="panel panel-default">
					<div id="collapseOne" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="headingOne">
						<div class="panel-body">
							<div class="form-group">
								{{ Form::label('junction_aa', __('short.junction_aa')) }}
								@include('help', ['id' => 'junction_aa'])
								{{ Form::text('junction_aa', '', array('class' => 'form-control', 'minlength' => '4', 'data-toggle' => 'tooltip', 'title' => 'Substring search (matches entire substring provided, minimum of 4 AA required). Will take a long time if millions of sequences are found.', 'data-placement' => 'bottom')) }}
							</div>
						</div>
					</div>
				</div>

				<div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
					<div class="panel panel-default">
						<div class="panel-heading" role="tab" id="headingThree">
							<h4 class="panel-title">
								<a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
									Sample level filters
								</a>
							</h4>
						</div>
						<div id="collapseThree" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="headingThree">
							<div class="panel-body">

							    <div class="form-group">
									{{ Form::label('organism', __('short.organism')) }}
									@include('help', ['id' => 'organism'])
									{{ Form::select('organism', $subject_organism_list, '', array('class' => 'form-control')) }}
								</div>

								<div class="form-group">
									{{ Form::label('cell_subset', __('short.cell_subset')) }}
									@include('help', ['id' => 'cell_subset'])
								    {{ Form::select('cell_subset[]', $cell_type_list, '', array('class' => 'form-control multiselect-ui', 'multiple' => 'multiple')) }}
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
						@if ( ! empty($rest_service_list_no_response_timeout))
							<p>Sorry, the statistics below are incomplete. These repositories did not return statistics before the Gateway time limit of {{ config('ireceptor.service_request_timeout') }} sec:</p>
							<ul>
								@foreach ($rest_service_list_no_response_timeout as $rs)
										<li>{{ $rs->display_name }}</li>
								@endforeach
							</ul>
							<p>But the sequence data can still be downloaded.</p>
							<p>For complete statistics, reduce the size of the data you're exploring, or download the sequence data and perform statistics offline.</p>
						@endif

						@if ( ! empty($rest_service_list_no_response_error))
							<p>Sorry, the statistics below may be incomplete. An unexpected error occurred when querying the following repositories:</p>
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
								@lang('short.' . $filter_key)
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


				@if (! empty($sequence_list))
					@if ($total_filtered_sequences > config('ireceptor.sequences_download_limit'))
						<a href="/sequences-download" class="btn btn-primary pull-right download_sequences" disabled="disabled" role="button" data-container="body" data-toggle="tooltip" data-placement="top" title="Downloads of more than {{ number_format(config('ireceptor.sequences_download_limit')) }} sequences will be possible in the near future." data-trigger="hover" tabindex="0">
							<span class="glyphicon glyphicon-download-alt" aria-hidden="true"></span>
							<span class="text">Download all {{number_format($total_filtered_sequences)}} sequences</span>
						</a>
					@else
						<a href="/sequences-download?query_id={{ $download_query_id }}&amp;n={{ $total_filtered_sequences }}&amp;page=sequences-quick-search" class="btn btn-primary pull-right download_sequences">
							<span class="glyphicon glyphicon-download-alt" aria-hidden="true"></span>
							<span class="text">Download all {{number_format($total_filtered_sequences)}} sequences <strong>{{ $download_time_estimate ? '(will take up to ' . $download_time_estimate . ')' : ''}}</strong></span>
						</a>
					@endif

					<h3> 
						Individual Sequences

						<small class="sequence_count">
							1-{{ count($sequence_list) }}
							of
							<span title="{{ number_format($total_filtered_sequences) }}">
								{{ human_number($total_filtered_sequences) }}
							</span>
						</small>

						<a class="btn btn-xs" data-toggle="collapse" href="#column_selector" aria-expanded="false" aria-controls="column_selector" title="Edit Columns">
						  <span class="glyphicon glyphicon-edit" aria-hidden="true"></span>
						  Customize columns
						</a>						
					</h3>
					
					<!-- table column selector -->
					<div class="collapse" id="column_selector">
						<div class="panel panel-default">
							<div class="panel-heading">
								<h4 class="panel-title">
									Edit Individual Sequences Columns
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

<script>
	var graphFields = [
	        "study_title",
	        "subject_id",
	        "sample_id",
	        "disease_diagnosis", 
	        "tissue",
	        "pcr_target_locus"
	    ];
	var graphNames = [
	        "@lang('short.study_title')", 
	        "@lang('short.subject_id')",
	        "@lang('short.sample_id')",
	        "@lang('short.disease_diagnosis')",
	        "@lang('short.tissue')", 
	        "@lang('short.pcr_target_locus')"
	    ];

	var graphInternalLabels = true;
	var graphCountField = "ir_filtered_sequence_count";
	var graphData = {!! $sample_list_json !!};
</script>
@stop