@extends('template')

@section('title', 'Browse sequences')

@section('content')
<div class="container-fluid sequence_container">

	<div class="row">
		<div class="col-md-2">
			{{ Form::open(array('url' => 'sequences', 'role' => 'form', 'method' => 'get')) }}

				@foreach ($hidden_fields as $hf)
					<input type="hidden" name="{{$hf['name']}}" value="{{$hf['value']}}">
				@endforeach

				<!-- template for fields added dynamically -->
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

				<!-- filters -->
			    <div class="filter_list">
					@foreach ($filters_list as $name => $title)
					    <div class="form-group">
							{{ Form::label($name, $title) }}
							<div class="input-group">
								{{ Form::text($name, '', array('class' => 'form-control')) }}
								<span class="input-group-btn">
			        				<button class="btn btn-default remove_field" type="button">
			        				<span class="glyphicon glyphicon-remove" aria-hidden="true"></span>
			        				</button>
			      				</span>
							</div>
						</div>
					@endforeach
				</div>

	    		<div class="form-group add_filter">
				    <div class="input-group">
						{{ Form::select('add_field', $filters_list_select, '', array('class' => 'form-control add_field')) }}
						<span class="input-group-btn">
		       				<button class="btn btn-default add_field" type="button">
			       				<span class="glyphicon glyphicon-plus" aria-hidden="true"></span>
		    	   				Add filter
		       				</button>
		      			</span>
					</div>
   				</div>				


				<p>{{ Form::submit('Update', array('class' => 'btn btn-primary')) }}</p>

				<p>{{ Form::submit('Download as CSV', array('class' => 'btn btn-primary', 'name' => 'csv')) }}</p>

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
			<!-- statistics box -->
			@if (! empty($sequence_list))
			@endif

			<div class="sequences_stats">
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
							Sequence data columns
							 <button type="button" class="close" data-toggle="collapse" href="#sequence_column_selector" aria-expanded="false" aria-controls="sequence_column_selector">
							 	<span aria-hidden="true">&times;</span>
							 </button>
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
									{{ $s->{$sequence_column_name->name} }}
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
@stop