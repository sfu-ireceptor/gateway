@extends('template')

@section('title', 'Browse sequences')

@section('content')
<div class="container-fluid sequence_container">

	<h1>Sequences <small>Filter and download sequences</small></h1>

	<div class="row">
		<div class="col-md-2 filters">

			<h3>Filters</h3>

			{{ Form::open(array('url' => 'sequences', 'role' => 'form', 'method' => 'get')) }}

				@foreach ($hidden_fields as $hf)
					<input type="hidden" name="{{$hf['name']}}" value="{{$hf['value']}}">
				@endforeach

{{-- 				<!-- template for fields added dynamically -->
				<div id="field_template" class="hidden">
					<div class="form-group">						
						<label for="junction_sequence_aa">CDR3 AA Sequence</label>
						<div class="input-group">
							<input type="text" id="junction_sequence_aa" value="" name="junction_sequence_aa" class="form-control">
							<span class="input-group-btn">
				    			<button type="button" class="btn btn-default remove_field">
				      				<span aria-hidden="true" class="glyphicon glyphicon-remove"></span>
				    			</button>
				      		</span>
						</div>
					</div>
				</div>
 --}}
				<div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
					<div class="panel panel-default">
						<div class="panel-heading" role="tab" id="headingOne">
							<h4 class="panel-title">
								<a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
									Filter by VDJ
								</a>
							</h4>
						</div>
						<div id="collapseOne" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingOne">
							<div class="panel-body">
								<div class="form-group">
									{{ Form::label('v_call', $filters_list_all['v_call']) }}
									{{ Form::text('v_call', '', array('class' => 'form-control')) }}
								</div>

								<div class="form-group">
									{{ Form::label('j_call', $filters_list_all['j_call']) }}
									{{ Form::text('j_call', '', array('class' => 'form-control')) }}
								</div>

								<div class="form-group">
									{{ Form::label('d_call', $filters_list_all['d_call']) }}
									{{ Form::text('d_call', '', array('class' => 'form-control')) }}
								</div>

{{-- 								@foreach ($filters_list_all as $name => $title)
								    <div class="form-group">
										{{ Form::label($name, $title) }}
										{{ Form::text($name, '', array('class' => 'form-control')) }}
									</div>
								@endforeach --}}
							</div>
						</div>
					</div>

					<div class="panel panel-default">
						<div class="panel-heading" role="tab" id="headingTwo">
							<h4 class="panel-title">
								<a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
									Filter by Junction AA
								</a>
							</h4>
						</div>
						<div id="collapseTwo" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingTwo">
							<div class="panel-body">
								<div class="form-group">
									{{ Form::label('junction_aa', $filters_list_all['junction_aa']) }}
									{{ Form::text('junction_aa', '', array('class' => 'form-control')) }}
								</div>

								<div class="form-group">
									{{ Form::label('junction_aa_length', $filters_list_all['junction_aa_length']) }}
									{{ Form::text('junction_aa_length', '', array('class' => 'form-control')) }}
								</div>
							</div>
						</div>
					</div>

					<div class="panel panel-default">
						<div class="panel-heading" role="tab" id="headingThree">
							<h4 class="panel-title">
								<a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
									Advanced filters
								</a>
							</h4>
						</div>
						<div id="collapseThree" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingThree">
							<div class="panel-body">
								<div class="form-group">
									{{ Form::label('annotation_tool', $filters_list_all['annotation_tool']) }}
									{{ Form::text('annotation_tool', '', array('class' => 'form-control')) }}
								</div>
								<div class="form-group">
									{{ Form::label('functional', $filters_list_all['functional']) }}
									{{ Form::text('functional', '', array('class' => 'form-control')) }}
								</div>
								<div class="form-group">
									{{ Form::label('rev_comp', $filters_list_all['rev_comp']) }}
									{{ Form::text('rev_comp', '', array('class' => 'form-control')) }}
								</div>
								<div class="form-group">
									{{ Form::label('v_score', $filters_list_all['v_score']) }}
									{{ Form::text('v_score', '', array('class' => 'form-control')) }}
								</div>
								<div class="form-group">
									{{ Form::label('d_score', $filters_list_all['d_score']) }}
									{{ Form::text('d_score', '', array('class' => 'form-control')) }}
								</div>
								<div class="form-group">
									{{ Form::label('j_score', $filters_list_all['j_score']) }}
									{{ Form::text('j_score', '', array('class' => 'form-control')) }}
								</div>
							</div>
						</div>
					</div>

				</div>


{{-- 			    <div class="filter_list">
				</div>

	    		<div class="form-group add_filter">
				    <div class="input-group">
						{{ Form::select('add_field', $filters_list_all_select, '', array('class' => 'form-control add_field')) }}
						<span class="input-group-btn">
		       				<button class="btn btn-default add_field" type="button">
			       				<span class="glyphicon glyphicon-plus" aria-hidden="true"></span>
		    	   				Add filter
		       				</button>
		      			</span>
					</div>
   				</div>	 --}}			


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
			{{ Form::close() }}				
		</div>

		<div class="col-md-10">

			<p>
				<strong>Active filters:</strong>

			</p>

			<p>
				<strong>{{number_format($total_filtered_sequences)}} sequences ({{ $total_filtered_samples }} samples) returned from:</strong>
				<span title="{{ $filtered_repositories_names }}", class="data_text_box">
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
				<table class="table table-striped  table-condensed">
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

				<!-- apps -->
				<h2>Analysis Apps</h2>

				@if (isset($system))

					<div role="tabpanel" class="analysis_apps_tabpanel">
						<!-- Tab links -->
						<ul class="nav nav-tabs" role="tablist">
							<li role="presentation" class="active"><a href="#app1" aria-controls="app1" role="tab" data-toggle="tab">Standard Histogram Generator</a></li>
							<li role="presentation"><a href="#app2" aria-controls="app2" role="tab" data-toggle="tab">Amazing Historgram Generator</a></li>
							<li role="presentation"><a href="#app3" aria-controls="app3" role="tab" data-toggle="tab">Nishanth 01</a></li>
						</ul>

						<!-- Tab panes -->
						<div class="tab-content">

							<div role="tabpanel" class="tab-pane active" id="app1">
				    			{{ Form::open(array('url' => 'jobs/launch-app', 'role' => 'form', 'target' => '_blank')) }}
									{{ Form::hidden('filters_json', $filters_json) }}
									{{ Form::hidden('data_url', $url) }}
									{{ Form::hidden('app_id', 1) }}

								    <div class="row">
								    	<div class="col-md-3">
										    <div class="form-group">
												{{ Form::label('var', 'Variable') }}
												{{ Form::select('var', $var_list, '', array('class' => 'form-control')) }}
											</div>
										</div>
									</div>

									{{ Form::submit('Generate using ' . $system->username . '@' . $system->host, array('class' => 'btn btn-primary')) }}
								{{ Form::close() }}
							</div>
							
							<div role="tabpanel" class="tab-pane" id="app2">
				    			{{ Form::open(array('url' => 'jobs/launch-app', 'role' => 'form', 'target' => '_blank')) }}
									{{ Form::hidden('filters_json', $filters_json) }}
									{{ Form::hidden('data_url', $url) }}
									{{ Form::hidden('app_id', 2) }}

								    <div class="row">
								    	<div class="col-md-3">
										    <div class="form-group">
												{{ Form::label('var', 'Variable') }}
												{{ Form::select('var', $var_list, '', array('class' => 'form-control')) }}
											</div>
										</div>
								    	<div class="col-md-3">
										    <div class="form-group">
												{{ Form::label('var', 'Color') }}
												{{ Form::select('color', $amazingHistogramGeneratorColorList, '', array('class' => 'form-control')) }}
											</div>
										</div>
									</div>

									{{ Form::submit('Generate using ' . $system->username . '@' . $system->host, array('class' => 'btn btn-primary')) }}
								{{ Form::close() }}
							</div>

							<div role="tabpanel" class="tab-pane" id="app3">
				    			{{ Form::open(array('url' => 'jobs/launch-app', 'role' => 'form', 'target' => '_blank')) }}
									{{ Form::hidden('filters_json', $filters_json) }}
									{{ Form::hidden('data_url', $url) }}
									{{ Form::hidden('app_id', 3) }}

									{{ Form::submit('Generate using ' . $system->username . '@' . $system->host, array('class' => 'btn btn-primary')) }}
								{{ Form::close() }}
							</div>

						</div>
					</div>
				@else
					<p>
						<a href="systems">Add a system</a> to be able to use analysis apps.
					</p>
				@endif
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
var graphDIV = "sequence_chart";
var graphInternalLabels = true;
var graphCountField = "ir_filtered_sequence_count";
var graphData = {!! $sample_list_json !!};
</script>
@stop