@extends('template')

@section('title', 'Quick Junction Search')

@section('content')
<div class="container-fluid sequence_container">

	<h1>Junction Search <small>Quickly filter by junction and download sequences</small></h1>

	<div class="row">
		<div class="col-md-2 filters">

			<h3>Filters</h3>

			{{ Form::open(array('url' => 'sequences-quick-search', 'role' => 'form', 'method' => 'get')) }}

				@foreach ($hidden_fields as $hf)
					<input type="hidden" name="{{$hf['name']}}" value="{{$hf['value']}}">
				@endforeach

					
				<div class="panel panel-default">
					<div id="collapseOne" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="headingOne">
						<div class="panel-body">
							<div class="form-group">
								{{ Form::label('junction_aa', $filters_list_all['junction_aa']) }}
								{{ Form::text('junction_aa', '', array('class' => 'form-control')) }}
							</div>
						</div>
					</div>
				</div>

				<div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
					<div class="panel panel-default">
						<div class="panel-heading" role="tab" id="headingThree">
							<h4 class="panel-title">
								<a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
									More filters
								</a>
							</h4>
						</div>
						<div id="collapseThree" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="headingThree">
							<div class="panel-body">
							    <div class="form-group">
									{{ Form::label('organism', __('short.organism')) }}
									{{ Form::text('organism', '', array('class' => 'form-control')) }}
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
						{{ Form::submit('Apply filters →', array('class' => 'btn btn-primary search_samples')) }}
					</p>

					<p>{{ Form::submit('↓ Download as CSV', array('class' => 'btn btn-primary', 'name' => 'csv')) }}</p>

					<p>
						<a class="bookmark" href="/system/" data-uri="{{ $url }}">
							@if ($bookmark_id)
								<button type="button" class="btn btn-success" aria-label="Bookmark" data-id="{{ $bookmark_id }}">
								  <span class="glyphicon glyphicon-star" aria-hidden="true"></span>
								  <span class="text">Bookmarked</span>
								</button>
							@else
								<button type="button" class="btn btn-default" aria-label="Bookmark">
								  <span class="glyphicon glyphicon-star-empty" aria-hidden="true"></span>
								  <span class="text">Bookmark</span>
								</button>
							@endif
						</a>
					</p>
   				</div>

			{{ Form::close() }}				
		</div>

		<div class="col-md-10">

{{-- 			<p>
				<strong>Active filters:</strong>
				@foreach($filter_fields as $filter_key => $filter_value)
					<span title= "@lang('short.' . $filter_key): {{$filter_value}}", class="data_text_box">
						@lang('short.' . $filter_key)
					</span>
				@endforeach
			</p> --}}

			<p>
				<strong>{{number_format($total_filtered_sequences)}} sequences ({{ $total_filtered_samples }} {{ str_plural('sample', $total_filtered_samples)}}) returned from:</strong>
				<span title="{{ $filtered_repositories_names }}", class="data_text_box">
					{{ $total_filtered_repositories }} remote {{ str_plural('repository', $total_filtered_repositories)}}
				</span>
				<span class="data_text_box">
					{{ $total_filtered_labs }} research {{ str_plural('lab', $total_filtered_labs)}}
				</span>
				<span class="data_text_box">
					{{ $total_filtered_studies }} {{ str_plural('study', $total_filtered_studies)}}
				</span>
			</p>

			<div class="data_container_box">
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


			@if (! empty($sequence_list))
				<!-- sequence data column selector -->
				<div class="collapse" id="sequence_column_selector">
					<div class="panel panel-default">
						<div class="panel-heading">
							<h4 class="panel-title">
								Sequence data columns
								 <button type="button" class="close" data-toggle="collapse" href="#sequence_column_selector" aria-expanded="false" aria-controls="sequence_column_selector">
									<span aria-hidden="true">&times;</span>
								 </button>
							</h4>
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
				<table class="table table-striped table-condensed">
					<thead>
						<tr>
							@foreach ($sequence_column_name_list as $sequence_column_name)
								<th class="text-nowrap seq_col_{{ $sequence_column_name->id }} {{ in_array($sequence_column_name->id, $current_sequence_columns) ? '' : 'hidden' }}">
									{{ $sequence_column_name->title }}
								</th>
							@endforeach
							<th class="column_selector">
								<a class="btn btn-default btn-xs" data-toggle="collapse" href="#sequence_column_selector" aria-expanded="false" aria-controls="sequence_column_selector">
					  				edit..
								</a>
							</th>
						</tr>
					</thead>
					<tbody>
						@foreach ($sequence_list as $s)
						<tr>
							@foreach ($sequence_column_name_list as $sequence_column_name)
									<td class="seq_col_{{ $sequence_column_name->id }} {{ in_array($sequence_column_name->id, $current_sequence_columns) ? '' : 'hidden' }}">
										@isset($s->{$sequence_column_name->name})
											{{ $s->{$sequence_column_name->name} }}
										@endisset
									</td>
							@endforeach
							<td class="column_selector"></td>
						</tr>
						@endforeach
					</tbody>
				</table>
			@endif
		</div>
	</div>
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