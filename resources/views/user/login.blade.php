@extends('template')

@section('title', 'Log In')

@section('content')
<div class="container login_container">

	<div class="row">
		<div class="col-md-3">
			
			<div class="login_logo">
				<img src="/images/logos/ireceptor_big.png">
			</div>

			<div class="login-box">
				<div class="panel panel-default">
					<div class="panel-body">
						{{ Form::open(array('role' => 'form')) }}
							<div class="text-danger">
								{{ $errors->first() }}
								@if ($errors->first())
									<p><a class="forgot" href="/user/forgot-password">Forgot your username or password?</a></p>
								@endif
							</div>

							<p>
								{{ Form::label('username', 'Username') }}
								{{ Form::text('username', '', array('class' => 'form-control')) }}
							</p>
							<p>
								{{ Form::label('password', 'Password') }}
								{{ $errors->first("password") }}
								{{ Form::password('password', array('class' => 'form-control')) }}
							</p>
							<p class="submit">
								{{ Form::submit('Log In →', array('class' => 'btn btn-primary')) }}
							</p>					
						{{ Form::close() }}
						<p><a href="/register"><strong>Create an account</strong></a></p>
					</div>
				</div>

				@isset($news)
					<div class="panel panel-warning beta_version">
						<div class="panel-heading">
							<h3 class="panel-title">What's New</h3>							
						</div>
						<div class="panel-body">
							{!! $news->message !!}
						</div>
						<div class="panel-footer">
							<a href="/news">Read all news →</a>
						</div>
					</div>
				@endisset
			</div>

		</div>

		<div class="col-md-9">

			<div class="intro">
				<p class="tagline">
					A <strong>science gateway</strong>
                    that enables the
                    <a href="http://www.ireceptor.org/node/108" class="external">discovery</a>,
                    <a href="http://www.ireceptor.org/node/204" class="external">analysis</a>,
                    and
                    <a href="http://www.ireceptor.org/node/97" class="external">download</a>
                    of
                    <a href="https://www.antibodysociety.org/the-airr-community/" class="external"> AIRR-seq data</a>
                    (antibody/B-cell and T-cell receptor repertoires)
                    from the
                    <a href="/repositories">{{ $total_repositories }} remote {{ str_plural('repository', $total_repositories)}}</a>
                    in the
                    <a href="https://www.antibodysociety.org/the-airr-community/airr-data-commons/" class="external">AIRR Data Commons (ADC)</a>.
                    Search for disease data (e.g. cancer, autoimmunity, infections) and healthy controls.
				</p>
			</div>

			<div class="intro2">
<!--
                <p>
                    <strong>
                    Summary of bulk and single-cell data from the
                    <a href="/repositories">{{ $total_repositories }} remote {{ str_plural('repository', $total_repositories)}}</a>
                    in the ADC.
                    </strong>
                </p>
                </br>
-->
				<p class="intro_login">
					<strong>{{ human_number($total_sequences) }} {{ str_plural('annotated sequence', $total_sequences)}}</strong>
                    (bulk/single-cell) from
                    {{ $total_samples_sequences }} {{ str_plural('repertoire', $total_samples_sequences)}},
					<a href="#" class="toggle_modal_rest_service_list_expanded">{{ $total_projects_sequences }} {{ str_plural('study', $total_projects_sequences)}}</a>,
					<a href="#" class="toggle_modal_rest_service_list_folded">{{ $total_repositories_sequences }} {{ str_plural('repository', $total_repositories_sequences)}}</a>
                    <span class="help" role="button" data-container="body" data-toggle="popover_form_field" data-placement="right" title="Sequence Help" data-content='<p>Click to visit the iReceptor Sequence Documentation for more information on working with Sequences.</p>' data-trigger="hover" tabindex="0">
                     <a href="http://www.ireceptor.org/node/199" target="_blank"><span class="glyphicon glyphicon-question-sign"></span></a>
                    </span>
					<!-- repos/labs/studies popup -->
					@include('rest_service_list', ['rest_service_list' => $rest_service_list_sequences, 'tab' => 'sequence'])
				</p>

				<div class="charts">
					<div class="row">
						<div class="col-md-4 chart" data-chart-data="{!! object_to_json_for_html($charts_data['chart3']) !!}"></div>
						<div class="col-md-4 chart" data-chart-data="{!! object_to_json_for_html($charts_data['chart4']) !!}"></div>
						<div class="col-md-4 chart" data-chart-data="{!! object_to_json_for_html($charts_data['chart5']) !!}"></div>
					</div>
				</div>
                <p class="intro_login">
                    <strong>{{ human_number($total_clones) }} {{ str_plural('clone', $total_clones)}}</strong>
                    aggregated
                    from
                    {{ $total_samples_clones }}
                    {{ str_plural('repertoire', $total_samples_clones)}},
                    <a href="#" class="toggle_modal_rest_service_list_clones_expanded">{{ $total_projects_clones}} {{ str_plural('study', $total_projects_clones)}}</a>,
                    <a href="#" class="toggle_modal_rest_service_list_clones_folded">{{ $total_repositories_clones }} {{ str_plural('repository', $total_repositories_clones)}}</a>
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

                <p class="intro_login">
                    <strong>
                    {{ human_number($total_cells) }} 
                    {{ str_plural('sorted, single B/T cell', $total_cells)}}
                    </strong>
                    with paired receptors, gene expression, and cell phenotype
                    from
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
	</div>
</div>

@endsection

@section('footer')
	@include('footer_detailed')
@endsection
