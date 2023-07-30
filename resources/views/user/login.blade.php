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
					that enables the discovery, analysis and download
                    of <a href="https://www.antibodysociety.org/the-airr-community/">
                    AIRR-seq data</a> (antibody/B-cell and T-cell receptor repertoires)
                    from studies on cancer (for example breast and ovarian),
                    autoimmune diseases (for example MS and SLE),
                    and infectious diseases (for example HIV and influenza) from the
<a href="#" class="toggle_modal_rest_service_list_folded">{{ $total_repositories }} remote {{ str_plural('repository', $total_repositories)}}</a>
                    of the
                    <a href="https://www.antibodysociety.org/the-airr-community/airr-data-commons/">AIRR Data Commons</a>.
				</p>
			</div>

			<div class="intro2">

				<p class="intro_login">
					<strong>{{ human_number($total_sequences) }} {{ str_plural('sequence', $total_sequences)}}</strong> from
					<strong>{{ $total_samples_sequences }} {{ str_plural('repertoire', $total_samples_sequences)}}</strong> and
					<a href="#" class="toggle_modal_rest_service_list_expanded">{{ $total_projects_sequences }} {{ str_plural('study', $total_projects_sequences)}}</a>
					across
					<a href="#" class="toggle_modal_rest_service_list_folded">{{ $total_repositories }} remote {{ str_plural('repository', $total_repositories)}}</a>
					<!-- repos/labs/studies popup -->
					@include('rest_service_list', ['tab' => 'sequence'])
				</p>

				<div class="charts">
					<div class="row">
						<div class="col-md-4 chart" data-chart-data="{!! object_to_json_for_html($charts_data['chart3']) !!}"></div>
						<div class="col-md-4 chart" data-chart-data="{!! object_to_json_for_html($charts_data['chart4']) !!}"></div>
						<div class="col-md-4 chart" data-chart-data="{!! object_to_json_for_html($charts_data['chart5']) !!}"></div>
					</div>
<!--
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
-->
				</div>
                <p class="intro_login">
                    <strong>{{ human_number($total_clones) }} {{ str_plural('clone', $total_clones)}}</strong> from
                    <strong>{{ $total_samples_clones }} {{ str_plural('repertoire', $total_samples_clones)}}</strong> and
                    <a href="#" class="toggle_modal_rest_service_list_expanded">{{ $total_projects_clones}} {{ str_plural('study', $total_projects_clones)}}</a>
                    across
                    <a href="#" class="toggle_modal_rest_service_list_folded">{{ $total_repositories }} remote {{ str_plural('repository', $total_repositories)}}</a>
                    <!-- repos/labs/studies popup -->
                    @include('rest_service_list', ['tab' => 'clone'])
                </p>
				<div class="charts">
					<div class="row">
						<div class="col-md-4 chart" data-chart-type="clones" data-chart-data="{!! object_to_json_for_html($clone_charts_data['chart3']) !!}"></div>
						<div class="col-md-4 chart" data-chart-type="clones" data-chart-data="{!! object_to_json_for_html($clone_charts_data['chart4']) !!}"></div>
						<div class="col-md-4 chart" data-chart-type="clones" data-chart-data="{!! object_to_json_for_html($clone_charts_data['chart5']) !!}"></div>
					</div>
			    </div>

                <p class="intro_login">
                    <strong>{{ human_number($total_cells) }} {{ str_plural('cell', $total_cells)}}</strong> from
                    <strong>{{ $total_samples_cells }} {{ str_plural('repertoire', $total_samples_cells)}}</strong> and
                    <a href="#" class="toggle_modal_rest_service_list_expanded">{{ $total_projects_cells }} {{ str_plural('study', $total_projects_cells)}}</a>
                    across
                    <a href="#" class="toggle_modal_rest_service_list_folded">{{ $total_repositories }} remote {{ str_plural('repository', $total_repositories)}}</a>
                    <!-- repos/labs/studies popup -->
                    @include('rest_service_list', ['tab' => 'cell'])
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
