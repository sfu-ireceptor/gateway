@extends('template')

@section('title', 'Home')

@section('content')
<div class="container home_container">

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
						<div class="col-md-4 chart" data-chart-data="{!! object_to_json_for_html($charts_data['study_type']) !!}"></div>
						<div class="col-md-4 chart" data-chart-data="{!! object_to_json_for_html($charts_data['organism']) !!}"></div>
						<div class="col-md-4 chart" data-chart-data="{!! object_to_json_for_html($charts_data['disease_diagnosis']) !!}"></div>
					</div>
					<div class="row">
						<div class="col-md-4 chart" data-chart-data="{!! object_to_json_for_html($charts_data['tissue']) !!}"></div>
						<div class="col-md-4 chart" data-chart-data="{!! object_to_json_for_html($charts_data['pcr_target_locus']) !!}"></div>
						<div class="col-md-4 chart" data-chart-data="{!! object_to_json_for_html($charts_data['template_class']) !!}"></div>
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
									{{ Form::text('junction_aa', '', array('class' => 'form-control', 'minlength' => '4')) }}
								</div>
							</div>
							<div class="col-md-5">
								<div class="form-group">
									{{ Form::label('cell_subset', __('short.cell_subset')) }}
									@include('help', ['id' => 'cell_subset'])
								    {{ Form::select('cell_subset[]', $cell_type_list, '', array('class' => 'form-control multiselect-ui', 'multiple' => 'multiple')) }}
								</div>
							</div>							
						</div>

						<div class="row">
							<div class="col-md-5">
							    <div class="form-group">
									{{ Form::label('organism', __('short.organism')) }}
									@include('help', ['id' => 'organism'])
									{{ Form::select('organism', $subject_organism_list, '', array('class' => 'form-control')) }}
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
