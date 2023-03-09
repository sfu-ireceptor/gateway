@extends('template')

@section('title', 'Home')

@section('content')
<div class="container home_container">

<div class="alert alert-info announcement" role="alert">
	<img class="logo" src="/images/logos/ireceptor_logo.png" alt="">
	<strong>Help us shape the future of the iReceptor Gateway</strong>
	<a  class="btn btn-success external"  target="_blank" role="button" href="https://www.surveymonkey.ca/r/TVCQJXB">Take the iReceptor Survey</a>
</div>


	<div class="row">

		<div class="col-md-8">
			<div class="intro_home">

				<p>
					<strong>{{ human_number($total_sequences) }} sequences</strong> and
					<strong>{{ $total_samples }} repertoires</strong> are currently available,<br>
					from
					<a href="#" class="toggle_modal_rest_service_list_folded">
						{{ $total_repositories }} remote {{ str_plural('repository', $total_repositories)}},</a>
					<a href="#" class="toggle_modal_rest_service_list_expanded">
						{{ $total_labs }} research {{ str_plural('lab', $total_labs)}} and
						{{ $total_projects }} {{ str_plural('study', $total_projects)}}.
					</a>

					@include('rest_service_list', ['tab' => 'sequence'])
				</p>

				<div class="charts">
					<div class="row">
						<div class="col-md-4 chart" data-chart-data="{!! object_to_json_for_html($charts_data['chart1']) !!}"></div>
						<div class="col-md-4 chart" data-chart-data="{!! object_to_json_for_html($charts_data['chart2']) !!}"></div>
						<div class="col-md-4 chart" data-chart-data="{!! object_to_json_for_html($charts_data['chart3']) !!}"></div>
					</div>
					<div class="row">
						<div class="col-md-4 chart" data-chart-data="{!! object_to_json_for_html($charts_data['chart4']) !!}"></div>
						<div class="col-md-4 chart" data-chart-data="{!! object_to_json_for_html($charts_data['chart5']) !!}"></div>
						<div class="col-md-4 chart" data-chart-data="{!! object_to_json_for_html($charts_data['chart6']) !!}"></div>
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
					<p>Find interesting sequences and sequence annotations by exploring study, subject, and sample metadata</p>

						<div class="row">
							<div class="col-md-2">
							</div>
							<div class="col-md-10">
								<p class="button_container">	
									<a  class="btn btn-primary search_samples" role="button" href="/samples">Browse Repertoire Metadata →</a>
								</p>
							</div>
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
