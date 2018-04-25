@extends('template')

@section('title', 'Sequence Quick Search')

@section('content')
<div class="container-fluid sequence_container">

	<h1>Sequence Quick Search</h1>
	<p class="sh1">Filter by sequence and sequence annotation features (e.g. Junction)</p>

	<div class="row loading_contents">
		<div class="col-md-2 filters">

			<h3 class="first">Filters</h3>

			{{ Form::open(array('url' => 'sequences-quick-search', 'role' => 'form', 'method' => 'get', 'class' => 'sequence_search')) }}

				@foreach ($hidden_fields as $hf)
					<input type="hidden" name="{{$hf['name']}}" value="{{$hf['value']}}">
				@endforeach

					
				<div class="panel panel-default">
					<div id="collapseOne" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="headingOne">
						<div class="panel-body">
							<div class="form-group">
								{{ Form::label('junction_aa', $filters_list_all['junction_aa']) }}
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
									@foreach ($subject_organism_list as $id => $name)
									<div class="checkbox">
										<label>
										{{ Form::checkbox('organism[]', $id) }}
										{{ $name }}
										</label>
									</div>
									@endforeach
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
							</div>
						</div>
					</div>
				</div>

   				<div class="button_container">
					<p>
						{{ Form::submit('Apply filters →', array('class' => 'btn btn-primary search_samples loading')) }}
					</p>
   				</div>

			{{ Form::close() }}				
		</div>

		<div class="col-md-10">

			<!-- Services which didn't respond -->
			@if ( ! empty($rest_service_list_no_response))
				<div class="alert alert-warning" role="alert">
					<p>Sorry, data is incomplete. No response from:</p>
					<ul>
						@foreach ($rest_service_list_no_response as $rs)
							<li>{{ $rs->name }}</li>
						@endforeach
					</ul>
					<p>Try again later.</p>
				</div>
			@endif

			<!-- Active filters -->
			@if ( ! empty($filter_fields))
				<div class="active_filters">
					<h3>Active filters</h3>

					@foreach($filter_fields as $filter_key => $filter_value)
						<span title= "@lang('short.' . $filter_key): {{$filter_value}}", class="label label-default">
							@lang('short.' . $filter_key)
						</span>
					@endforeach

{{-- 				@isset($no_filters_query_id)
						<a href="/sequences?query_id={{ $no_filters_query_id }}" class="remove_filters">
							Remove filters
						</a>
					@endisset --}}

					<a class="bookmark" href="/system/" data-uri="{{ $url }}">
						@if ($bookmark_id)
							<button type="button" class="btn btn-success btn-xs" aria-label="Bookmark" data-id="{{ $bookmark_id }}">
							  <span class="glyphicon glyphicon-star" aria-hidden="true"></span>
							  <span class="text">Bookmarked</span>
							</button>
						@else
							<button type="button" class="btn btn-default btn-xs" aria-label="Bookmark">
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

					<div id="sequence_charts" class="charts">
						<div class="row">
							<div class="col-md-2 chart" id="sequence_chart1"></div>
							<div class="col-md-2 chart" id="sequence_chart2"></div>
							<div class="col-md-2 chart" id="sequence_chart3"></div>
							<div class="col-md-2 chart" id="sequence_chart4"></div>
							<div class="col-md-2 chart" id="sequence_chart5"></div>
							<div class="col-md-2 chart" id="sequence_chart6"></div>
						</div>
					</div>										
				</div>
			@endif


			@if (! empty($sequence_list))
				<a href="#" class="btn btn-primary pull-right download_sequences">
					<span class="glyphicon glyphicon-download-alt" aria-hidden="true"></span>
					<span class="text">Download all {{number_format($total_filtered_sequences)}} sequences</span>
				</a>

				<h3>
					Individual Sequences
					<small class="sequence_count">
						1-{{ count($sequence_list) }} of {{number_format($total_filtered_sequences)}}
					</small>
				</h3>

				<!-- sequence data column selector -->
				<div class="collapse" id="sequence_column_selector">
					<div class="panel panel-default">
						<div class="panel-heading">
							<button class="btn btn-default btn-xs pull-right" data-toggle="collapse" href="#sequence_column_selector" aria-expanded="false" aria-controls="sequence_column_selector">
					  			Done
							</button>
							<h4 class="panel-title">Edit Individual Sequences Columns</h4>
						</div>
				  		<div class="panel-body">
							<form class="sequence_column_selector">
								@foreach ($sequence_column_name_list as $sequence_column_name)
									<div class="checkbox">
										<label>
											<input name="sequence_columns" class="{{ $sequence_column_name->name }}" data-id="{{ $sequence_column_name->id }}" type="checkbox" value="{{'seq_col_' . $sequence_column_name->id}}" {{ in_array($sequence_column_name->id, $current_sequence_columns) ? 'checked="checked"' : '' }} />
											{{ $sequence_column_name->title }}
										</label>
									</div>		
								@endforeach
							</form>
				  		</div>
					</div>
				</div>

				<!-- sequence data -->
				<table class="table table-striped table-condensed much_data table-bordered">
					<thead>
						<tr>
							<th class="checkbox_cell">
								<a class="btn btn-default btn-xs" data-toggle="collapse" href="#sequence_column_selector" aria-expanded="false" aria-controls="sequence_column_selector" title="Edit Columns">
								  <span class="glyphicon glyphicon-edit" aria-hidden="true"></span>
								</a>
							</th>
							@foreach ($sequence_column_name_list as $sequence_column_name)
								<th class="text-nowrap seq_col_{{ $sequence_column_name->id }} {{ in_array($sequence_column_name->id, $current_sequence_columns) ? '' : 'hidden' }}">
									{{ $sequence_column_name->title }}
								</th>
							@endforeach
						</tr>
					</thead>
					<tbody>
						@foreach ($sequence_list as $s)
						<tr>
							<td></td>
							@foreach ($sequence_column_name_list as $sequence_column_name)
									<td class="seq_col_{{ $sequence_column_name->id }} {{ in_array($sequence_column_name->id, $current_sequence_columns) ? '' : 'hidden' }}">
										@isset($s->{$sequence_column_name->name})
											{{ $s->{$sequence_column_name->name} }}
										@endisset
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
	        "@lang('v2.study_title')",
	        "@lang('v2.sample_id')",
	        "@lang('v2.disease_state_sample')", 
	        "@lang('v2.tissue')",
	        "@lang('v2.cell_subset')"
	    ];
	var graphNames = [
	        "@lang('short.study_description')",
	        "@lang('short.study_title')", 
	        "@lang('short.sample_id')",
	        "@lang('short.disease_state_sample')",
	        "@lang('short.tissue')", 
	        "@lang('short.cell_subset')"
	    ];
var graphDIV = "sequence_chart";
var graphInternalLabels = true;
var graphLabelLength = 10;
var graphCountField = "ir_filtered_sequence_count";
var graphData = {!! $sample_list_json !!};
</script>
@stop