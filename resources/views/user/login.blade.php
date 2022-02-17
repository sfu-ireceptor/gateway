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
						<p>Apply for an account by emailing <a href="mailto:support@ireceptor.org">support@ireceptor.org</a>.</p>
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
					<span>A <strong>science gateway</strong></span>
					<span>that enables the discovery, analysis and download</span>
					<span>of <a href="https://www.antibodysociety.org/the-airr-community/">AIRR-seq data</a> (antibody/B-cell and T-cell receptor repertoires)</span>
					<span>from multiple independent repositories (the <a href="https://www.antibodysociety.org/the-airr-community/airr-data-commons/">AIRR Data Commons</a>), including:</span>
				</p>
				<div class="row">
					<div class="col-md-1">
					</div>
					<div class="col-md-5">
						<ul class="repositories">
							<li>
								<a href="http://ireceptor.org" class="external" target="_blank">iReceptor Public Archive</a>
							</li>
							<li>
								<a href="http://ireceptor.org/covid19" class="external" target="_blank">AIRR COVID-19</a>
							</li>
							<li>
								<a href="https://vdjserver.org/" class="external" target="_blank">VDJServer</a>
							</li>
						</ul>
					</div>
					@if (env('APP_ENV') != 'production')
						<div class="col-md-5">
							<ul class="repositories">
							</ul>
						</div>
					@endif
				</div>
			</div>

			<div class="intro2">

				<p class="intro_login">
					Search study metadata and sequence features from
					<strong>{{ human_number($total_sequences) }} {{ str_plural('sequence', $total_sequences)}}</strong>,
					<strong>{{ $total_samples }} {{ str_plural('repertoire', $total_samples)}}</strong>, and
					<a href="#" class="toggle_modal_rest_service_list_expanded">{{ $total_studies }} {{ str_plural('study', $total_studies)}}</a>
					across
					<a href="#" class="toggle_modal_rest_service_list_folded">{{ $total_repositories }} remote {{ str_plural('repository', $total_repositories)}}</a>.
					<!-- repos/labs/studies popup -->
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
	</div>
</div>

@endsection

@section('footer')
	@include('footer_detailed')
@endsection
