@extends('template')

@section('title', 'Cell Search')
@section('sample_query_id', $sample_query_id)

@section('content')

<div class="banner_title cells">
	<h1>2. Cell Search</h1>
	<p class="sh1">Filter by cell and cell annotation feature</p>
</div>

<div class="container-fluid cell_container">

	<div class="row">		
		<div class="col-md-2 filters">

			<h3 class="first">Filters</h3>

			{{ Form::open(array('url' => 'cells', 'role' => 'form', 'method' => 'post', 'class' => 'cell_search standard_cell_search show_reloading_message')) }}

				@foreach ($hidden_fields as $hf)
					<input type="hidden" name="{{$hf['name']}}" value="{{$hf['value']}}">
				@endforeach

				<input type="hidden" name="sample_query_id" value="{{ $sample_query_id }}" />



				<div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">

					<div class="panel panel-default">
						<div class="panel-heading" role="tab" id="headingOne">
							<h4 class="panel-title">
								<a role="button" class="{{ in_array('0', $open_filter_panel_list) ? '' : 'collapsed' }}" data-toggle="collapse" data-parent="#accordion" href="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
									Filter by Cell
								</a>
							</h4>
						</div>
						<div id="collapseOne" class="panel-collapse collapse {{ in_array('0', $open_filter_panel_list) ? 'in' : '' }}" role="tabpanel" aria-labelledby="headingOne">
							<div class="panel-body">
								<div class="form-group">
									{{ Form::label('expression_study_method_cell',  __('short.expression_study_method_cell')) }}
									@include('help', ['id' => 'expression_study_method_cell'])
									{{ Form::text('expression_study_method_cell', '', array('class' => 'form-control', 'data-toggle' => 'tooltip', 'data-placement' => 'bottom')) }}
								</div>

								<div class="form-group">
									{{ Form::label('virtual_pairing_cell', __('short.virtual_pairing_cell')) }}
									@include('help', ['id' => 'virtual_pairing_cell'])
									{{ Form::text('virtual_pairing_cell', '', array('class' => 'form-control', 'data-toggle' => 'tooltip', 'data-placement' => 'bottom')) }}
								</div>

								<p class="button_container">
									{{ Form::submit('Apply filters →', array('class' => 'btn btn-primary search_samples')) }}
								</p>

							</div>
						</div>
					</div>

					<div class="panel panel-default">
						<div class="panel-heading" role="tab" id="headingOne">
							<h4 class="panel-title">
								<a role="button" class="{{ in_array('1', $open_filter_panel_list) ? '' : 'collapsed' }}" data-toggle="collapse" data-parent="#accordion" href="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
									Filter by Cell Expression
								</a>
							</h4>
						</div>
						<div id="collapseTwo" class="panel-collapse collapse {{ in_array('1', $open_filter_panel_list) ? 'in' : '' }}" role="tabpanel" aria-labelledby="headingOne">
							<div class="panel-body">
								<div class="form-group">
									{{ Form::label('property_expression',  'Property Label') }}
									@include('help', ['id' => 'property_expression'])
									{{ Form::text('property_expression', '', array('class' => 'form-control', 'data-toggle' => 'tooltip', 'data-placement' => 'bottom')) }}
								</div>

								<div class="form-group">
									{{ Form::label('value_expression', 'Minimum Expression Value') }}
									@include('help', ['id' => 'value_expression'])
									{{ Form::text('value_expression', '', array('class' => 'form-control', 'data-toggle' => 'tooltip', 'data-placement' => 'bottom')) }}
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
							<p>For accurate statistics, try to reduce the size of the data you're exploring. You can also download the cells and perform complex analyses offline.</p>
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
				@if ( ! empty($filter_fields) || ! empty($sample_filter_fields))
					<div class="active_filters">
						<h3>Active filters</h3>

						@if ( ! empty($sample_filter_fields))
							Repertoire Metadata filters:
							@foreach($sample_filter_fields as $filter_key => $filter_value)
								<span title= "@lang('short.' . $filter_key): {{$filter_value}}", class="label label-default">
									@lang('short.' . $filter_key)
								</span>
							@endforeach
							@isset($sample_query_id)
								<a href="/samples?query_id=@yield('sample_query_id', '')"class="remove_filters">
									Go back to Repertoire Metadata Search
								</a>
							@endisset						
							<br>
						@endif

						@if ( ! empty($filter_fields))
							Cell filters:
							@foreach($filter_fields as $filter_key => $filter_value)
								<a title= "@lang('short.' . $filter_key): {{ $filter_value }}" href="/cells?query_id={{ $query_id }}&amp;remove_filter={{ $filter_key }}" class="label label-primary">
									<span class="glyphicon glyphicon-remove" aria-hidden="true"></span>
									@lang('short.' . $filter_key)
								</a>
							@endforeach

							<a href="/cells?query_id={{ $query_id }}&amp;remove_filter=all" class="remove_filters">
								Remove all cell filters
							</a>
						@endif


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

				@if (empty($cell_list))
					<!-- No results -->
					<div class="no_results">
						<h2>No Results</h2>
						<p>
							Remove a filter
							@isset($no_filters_query_id)
								or <a href="/cells?query_id={{ $no_filters_query_id }}">remove all filters</a>
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
								<span title="{{ number_format($total_filtered_cells) }}">
									{{ number_format($total_filtered_cells) }} {{ str_plural('cell', $total_filtered_cells)}}
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
						@include('rest_service_list_cells', ['total_repositories' => $total_filtered_repositories, 'total_labs' => $total_filtered_labs, 'total_projects' => $total_filtered_studies, 'tab' => 'cell'])

						<div class="charts">
							<div class="row">
								<div class="col-md-2 chart" data-chart-type="cells" data-chart-data="{!! object_to_json_for_html($charts_data['chart1']) !!}"></div>
								<div class="col-md-2 chart" data-chart-type="cells" data-chart-data="{!! object_to_json_for_html($charts_data['chart2']) !!}"></div>
								<div class="col-md-2 chart" data-chart-type="cells" data-chart-data="{!! object_to_json_for_html($charts_data['chart3']) !!}"></div>
								<div class="col-md-2 chart" data-chart-type="cells" data-chart-data="{!! object_to_json_for_html($charts_data['chart4']) !!}"></div>
								<div class="col-md-2 chart" data-chart-type="cells" data-chart-data="{!! object_to_json_for_html($charts_data['chart5']) !!}"></div>
							</div>
						</div>
									
					</div>
				@endif 
				
				@if (! empty($cell_list))
					@if ($total_filtered_cells > config('ireceptor.cells_download_limit'))
						<a href="/cells-download" class="btn btn-primary pull-right download_cells" disabled="disabled" role="button" data-container="body" data-toggle="tooltip" data-placement="top" title="Downloads of more than {{ number_format(config('ireceptor.cells_download_limit')) }} cells will be possible in the near future." data-trigger="hover" tabindex="0">
							<span class="glyphicon glyphicon-download-alt" aria-hidden="true"></span>
							<span class="text">Download all {{number_format($total_filtered_cells)}} cells</span>
						</a>
					@else
						<a href="/cells-download?query_id={{ $query_id }}&amp;n={{ $total_filtered_cells }}&amp;page=cells" class="btn btn-primary btn-cells pull-right download_cells">
							<span class="glyphicon glyphicon-download-alt" aria-hidden="true"></span>
							<span class="text">Download all {{number_format($total_filtered_cells)}} cells <strong>{{ $download_time_estimate ? '(will take up to ' . $download_time_estimate . ')' : ''}}</strong></span>
						</a>
					@endif

					<h3> 
						Individual Cells
						<small class="cell_count">
							1-{{ count($cell_list) }}
							of
							<span title="{{ number_format($total_filtered_cells) }}">
								{{ human_number($total_filtered_cells) }}
							</span>
						</small>

						<a class="btn btn-xs" data-toggle="collapse" href="#column_selector" aria-expanded="false" aria-controls="column_selector" title="Edit Columns">
						  <span class="glyphicon glyphicon-edit" aria-hidden="true"></span>
						  Customize displayed columns
						</a>
					</h3>

					<!-- table column selector -->
					@include('columnSelector')

					<!-- cell data -->
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
							@foreach ($cell_list as $s)
							<tr>
								@foreach ($field_list as $field)
									<td class="text-nowrap col_{{ $field['ir_id'] }} {{ in_array($field['ir_id'], $current_columns) ? '' : 'hidden' }}">
										@isset($s->{$field['ir_id']})
											@if($field['ir_id'] == 'functional')
												{{ $s->functional ? 'Yes' : 'No' }}											
											@elseif($field['ir_id'] == 'v_call' || $field['ir_id'] == 'v_call' || $field['ir_id'] == 'd_call' )
												<span title="{{ $s->{$field['ir_id']} }}">
													{{ str_limit($s->{$field['ir_id']}, $limit = 30, $end = '‥') }}
												</span>
											@else
												@if(is_object($s->{$field['ir_id']}))
													<span title="{{ json_encode($s->{$field['ir_id']}) }}">
														{{ json_encode($s->{$field['ir_id']}) }}												
													</span>
												@elseif (is_array($s->{$field['ir_id']}))
													<span title="{{ implode(', ', $s->{$field['ir_id']}) }}">
														{{ str_limit(implode(', ', $s->{$field['ir_id']}), $limit = 40, $end = '‥') }}									
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
                    @if(config('services.agave.enabled'))
                        <!-- apps -->
                        <h2>Analysis Apps</h2>

                        @if (count($app_list) > 0 && $total_filtered_cells <= config('ireceptor.cells_download_limit'))

                            <div role="tabpanel" class="analysis_apps_tabpanel">
                                <!-- Tab links -->
                                <ul class="nav nav-tabs" role="tablist">
                                    @php $count = 0 @endphp
                                    @foreach ($app_list as $app)
                                        @if ( $count === 0)
                                            <li role="presentation" class="active"><a href="#{{$app['app_tag']}}" aria-controls="{{$app['app_tag']}}" role="tab" data-toggle="tab">{{$app['name']}}</a></li>
                                        @else
                                            <li role="presentation"><a href="#{{$app['app_tag']}}" aria-controls="{{$app['app_tag']}}" role="tab" data-toggle="tab">{{$app['name']}}</a></li>
                                        @endif
                                        @php $count = $count + 1 @endphp
                                    @endforeach
                                </ul>

                                <!-- Tab panes -->
                                <div class="tab-content">
                                    @php $count = 0 @endphp
                                    @foreach ($app_list as $app)
                                        @if ( $count === 0)
                                            <div role="tabpanel" class="tab-pane active" id="{{$app['app_tag']}}">
                                        @else
                                            <div role="tabpanel" class="tab-pane" id="{{$app['app_tag']}}">
                                        @endif
                                        {{ Form::open(array('url' => 'jobs/launch-app', 'role' => 'form', 'target' => '_blank')) }}
                                        {{ Form::hidden('filters_json', $filters_json) }}
                                        {{ Form::hidden('data_url', $url) }}
                                        {{ Form::hidden('app_id', $app['app_id']) }}

                                        <div class="row">
                                        <div class="col-md-10">
                                            <h3>{{$app['description']}} <span class="help" role="button" data-container="body" data-toggle="popover_form_field" data-placement="right" data-content="<p>{{$app['info']}}</p>" data-trigger="hover" tabindex="0"> <span class="glyphicon glyphicon-question-sign"></span></span></h3>
                                        </div>
                                        </div>

                                        @foreach ($app['parameter_list'] as $parameter)
                                            <div class="row">
                                            <div class="col-md-3">
                                            <div class="form-group">
                                                {{ Form::label($parameter['label'], $parameter['name']) }}
                                                <span class="help" role="button" data-container="body" data-toggle="popover_form_field" data-placement="right" data-content="<p>{{$parameter['description']}}</p>" data-trigger="hover" tabindex="0"> <span class="glyphicon glyphicon-question-sign"></span></span>
                                                @if ( ! empty($parameter['choices']) )
                                                    {{ Form::select($parameter['label'], $parameter['choices'], '', array('class' => 'form-control')) }}
                                                @else
                                                    {{ Form::text($parameter['label'], $parameter['default'], array('class' => 'form-control')) }}
                                                @endif
                                            </div>
                                            </div>
                                            </div>
                                        @endforeach

                                        <!-- Job control parameters - uncomment this if you want user control.
                                        <div class="row">
                                        <div class="col-md-10">
                                            <h3>Job control parameters<span class="help" role="button" data-container="body" data-toggle="popover_form_field" data-placement="right" data-content="<p>Parameters to control job resources used</p>" data-trigger="hover" tabindex="0"> <span class="glyphicon glyphicon-question-sign"></span></span></h3>
                                        </div>
                                        </div>

                                        @foreach ($app['job_parameter_list'] as $job_parameter)
                                            <div class="row">
                                            <div class="col-md-3">
                                            <div class="form-group">
                                                {{ Form::label($job_parameter['label'], $job_parameter['name']) }}
                                                <span class="help" role="button" data-container="body" data-toggle="popover_form_field" data-placement="right" data-content="<p>{{$job_parameter['description']}}</p>" data-trigger="hover" tabindex="0"> <span class="glyphicon glyphicon-question-sign"></span></span>
                                                @if ( ! empty($job_parameter['choices']) )
                                                    {{ Form::select($job_parameter['label'], $job_parameter['choices'], '', array('class' => 'form-control')) }}
                                                @else
                                                    {{ Form::text($job_parameter['label'], $job_parameter['default'], array('class' => 'form-control')) }}
                                                @endif
                                            </div>
                                            </div>
                                            </div>
                                        @endforeach
                                        -->

                                        {{ Form::submit('Submit ' . $app['name'] . ' analysis job', array('class' => 'btn btn-primary')) }}
                                        {{ Form::close() }}

                                        </div>
                                        @php $count = $count + 1 @endphp
                                    @endforeach

                                </div>
                            </div>
                        @elseif ($total_filtered_cells > config('ireceptor.cells_download_limit'))
                            <p>Sorry, analyses of more than {{ number_format(config('ireceptor.cells_download_limit')) }} Cells will be possible in the near future.</p>
                        @else
                            <p>No Analysis Apps for Cells available</p>
                        @endif
                    @endif
				@endif
			<div>
		</div>
	</div>
</div>
</div>

@include('reloadingMessage')
@include('loadingMessage')

@stop
