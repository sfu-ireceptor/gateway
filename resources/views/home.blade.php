@extends('template')

@section('title', 'Home')

@section('content')
@if ( $home_banner_display)
<div class="container home_container">
  <div class="intro_home">
    {!! $home_banner_text !!}
  </div>
</div>
@endif
<div class="container home_container">
	<div class="row">

		<div class="col-md-8">
			<div class="intro_home">

				<p>
                    <strong>{{ human_number($total_sequences) }} annotated sequences</strong>
                    from  
					{{ $total_samples_sequences }} repertoires, 
					<a href="#" class="toggle_modal_rest_service_list_expanded">
					{{ $total_projects_sequences }} {{ str_plural('study', $total_projects_sequences)}}
					</a>
                    ,
					<a href="#" class="toggle_modal_rest_service_list_folded">
                    {{ $total_repositories_sequences }} {{ str_plural('repository', $total_repositories_sequences)}}
                    </a>
                    <span class="help" role="button" data-container="body" data-toggle="popover_form_field" data-placement="right" title="Sequence Help" data-content='<p>Click to visit the iReceptor Sequence Documentation for more information on working with Sequences.</p>' data-trigger="hover" tabindex="0">
                     <a href="http://www.ireceptor.org/node/199" target="_blank"><span class="glyphicon glyphicon-question-sign"></span></a>
                    </span>


					@include('rest_service_list', ['tab' => 'sequence'])
				</p>

				<div class="charts">
					<div class="row">
						<div class="col-md-4 chart" data-chart-data="{!! object_to_json_for_html($charts_data['chart3']) !!}"></div>
						<div class="col-md-4 chart" data-chart-data="{!! object_to_json_for_html($charts_data['chart4']) !!}"></div>
						<div class="col-md-4 chart" data-chart-data="{!! object_to_json_for_html($charts_data['chart5']) !!}"></div>
					</div>
				</div>

				<p>
                    <strong>{{ human_number($total_clones) }} {{ str_plural('clone', $total_clones)}}</strong>
                    aggregated
                    from
                    {{ $total_samples_clones }}
                    {{ str_plural('repertoire', $total_samples_clones)}},
                    <a href="#" class="toggle_modal_rest_service_list_clones_expanded">
                    {{ $total_projects_clones}} {{ str_plural('study', $total_projects_clones)}}
                    </a>,
                    <a href="#" class="toggle_modal_rest_service_list_clones_folded">
                    {{ $total_repositories_clones }} {{ str_plural('repository', $total_repositories_clones)}}
                    </a>
                    <span class="help" role="button" data-container="body" data-toggle="popover_form_field" data-placement="right" title="Clone Help" data-content='<p>Click to visit the iReceptor Clone Documentation for more information on working with Clones.</p>' data-trigger="hover" tabindex="0">
                     <a href="http://www.ireceptor.org/node/200" target="_blank"><span class="glyphicon glyphicon-question-sign"></span></a>
                     </span>

                    <!-- repos/labs/studies popup -->
                    @include('rest_service_list_clones', ['rest_service_list' => $rest_service_list_clones, 'tab' => 'clone'])
				</p>

				<div class="charts">
					<div class="row">
						<div class="col-md-4 chart" data-chart-type="clones" data-chart-data="{!! object_to_json_for_html($clone_charts_data['chart3']) !!}"></div>
						<div class="col-md-4 chart" data-chart-type="clones" data-chart-data="{!! object_to_json_for_html($clone_charts_data['chart4']) !!}"></div>
						<div class="col-md-4 chart" data-chart-type="clones" data-chart-data="{!! object_to_json_for_html($clone_charts_data['chart5']) !!}"></div>
					</div>
				</div>

				<p>
                    <strong>
                    {{ human_number($total_cells) }}
                    {{ str_plural('sorted, single B/T cell', $total_cells)}}
                    </strong>
                    with paired receptors, gene expression, and cell phenotype from
                    {{ $total_samples_cells }}
                    {{ str_plural('repertoire', $total_samples_cells)}},
                    <a href="#" class="toggle_modal_rest_service_list_cells_expanded">{{ $total_projects_cells }} {{ str_plural('study', $total_projects_cells)}}</a>,
                    <a href="#" class="toggle_modal_rest_service_list_cells_folded">{{ $total_repositories_cells }} {{ str_plural('repository', $total_repositories_cells)}}</a>
                    <span class="help" role="button" data-container="body" data-toggle="popover_form_field" data-placement="right" title="Cell Help" data-content='<p>Click to visit the iReceptor Cell Documentation for more information on working with Cells.</p>' data-trigger="hover" tabindex="0">
                     <a href="http://www.ireceptor.org/node/201" target="_blank"><span class="glyphicon glyphicon-question-sign"></span></a>
                     </span>
                    <!-- repos/labs/studies popup -->
                    @include('rest_service_list_cells', ['rest_service_list' => $rest_service_list_cells, 'tab' => 'cell'])
				</p>

				<div class="charts">
					<div class="row">
						<div class="col-md-4 chart" data-chart-type="cells" data-chart-data="{!! object_to_json_for_html($cell_charts_data['chart4']) !!}"></div>
						<div class="col-md-4 chart" data-chart-type="cells" data-chart-data="{!! object_to_json_for_html($cell_charts_data['chart5']) !!}"></div>
						<div class="col-md-4 chart" data-chart-type="cells" data-chart-data="{!! object_to_json_for_html($cell_charts_data['chart6']) !!}"></div>
					</div>
				</div>

			</div>
		</div>

		<div class="col-md-4 side_search_links">
			<div class="panel panel-default">
				<div class="panel-heading">
					<h3 class="panel-title">Repertoire Metadata Search</h3>
				</div>
				<div class="panel-body filters sequence_search">
					<p>Find interesting sequence annotations, clones, and cells by exploring study, subject, and sample metadata</p>

								<p class="button_container">	
									<a  class="btn btn-primary search_samples" role="button" href="/samples">Browse Sequence Repertoire Metadata →</a>
								</p>
							<div class="col-md-10">
							</div>
								<p class="button_container">	
									<a  class="btn btn-primary btn-clones search_samples" role="button" href="/samples/clone?query_id=">Browse Clone Repertoire Metadata →</a>
								</p>
							<div class="col-md-10">
							</div>
								<p class="button_container">	
									<a  class="btn btn-primary btn-cells search_samples" role="button" href="/samples/cell?query_id=">Browse Cell Repertoire Metadata →</a>
								</p>
							<div class="col-md-10">
							</div>
						<div class="row">
						</div>

				</div>
			</div>

			<div class="panel panel-default sequence_search_container">
				<div class="panel-heading">
					<h3 class="panel-title">Sequence Quick Search</h3>
				</div>
				<div class="panel-body filters sequence_search">
					{{ Form::open(array('url' => 'sequences-quick-search', 'role' => 'form', 'method' => 'post', 'class' => 'sequence_search show_loading_message')) }}
							
						<p>Find sequences through all repositories with a specific Junction/CDR3 AA substring.</p>
	
						<div class="row">
							<div class="col-md-7">
								<div class="form-group junction_aa">
									{{ Form::label('junction_aa', 'Junction/CDR3 AA') }}
									@include('help', ['id' => 'junction_aa'])
									{{ Form::text('junction_aa', '', array('class' => 'form-control', 'minlength' => '4', 'data-toggle' => 'tooltip', 'title' => 'Substring search (matches entire substring provided, minimum of 4 AA required). Takes several minutes if millions of sequences are found.', 'data-placement' => 'bottom')) }}
								</div>
							</div>
							<div class="col-md-5">
								<div class="form-group">
									{{ Form::label('cell_subset', __('short.cell_subset')) }}
									@include('help', ['id' => 'cell_subset'])
								    {{ Form::select('cell_subset_id[]', $cell_type_ontology_list, '', array('class' => 'form-control multiselect-ui', 'multiple' => 'multiple')) }}
								</div>
							</div>							
						</div>

						<div class="row">
							<div class="col-md-5">
							    <div class="form-group">
									{{ Form::label('organism', __('short.organism')) }}
									@include('help', ['id' => 'organism'])
									{{ Form::select('organism_id[]', $subject_organism_ontology_list, '', array('class' => 'form-control multiselect-ui', 'multiple' => 'multiple')) }}
								</div>
							</div>
							<div class="col-md-7">
								<div class="button_container">
									<p>
										{{ Form::submit('Search', array('class' => 'btn btn-primary search_samples')) }}
									</p>
								</div>
							</div>
						</div>

					{{ Form::close() }}		
				</div>
			</div>
		</div>

	</div>
	
</div>
@stop

@section('footer')
	@include('footer_detailed')
@endsection
