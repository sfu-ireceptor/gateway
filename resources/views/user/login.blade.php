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
								{{ Form::text('username', '', array('class' => 'form-control', 'placeholder' => 'e.g. \'jane_lee\'')) }}
							</p>
							<p>
								{{ Form::label('password', 'Password') }}
								{{ $errors->first("password") }}
								{{ Form::password('password', array('class' => 'form-control', 'placeholder' => 'e.g. \'sx4KL2\'')) }}
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
					</div>
				@endisset
			</div>

		</div>

		<div class="col-md-9">
			<div class="intro">
				<p>
					A <strong>gateway</strong>
					to curated, distributed databases<br>
					containing "Next Generation" sequence data from immune responses<br>
					available for exploration, analysis, and download.
				</p>
			</div>
			<div class="intro2">
				<p>
					<strong>{{ human_number($total_sequences) }} sequences</strong> and
					<strong>{{ $total_samples }} samples</strong> are currently available,<br>
					from
					{{ $total_repositories }} remote {{ str_plural('repository', $total_repositories)}},
					{{ $total_labs }} research {{ str_plural('lab', $total_labs)}} and
					{{ $total_projects }} {{ str_plural('study', $total_projects)}}.
				</p>
				<div id="landing_charts">
					<div class="row">
						<div class="col-md-4 chart" id="landing_chart1"></div>
						<div class="col-md-4 chart" id="landing_chart2"></div>
						<div class="col-md-4 chart" id="landing_chart3"></div>
					</div>
					<div class="row">
						<div class="col-md-4 chart" id="landing_chart4"></div>
						<div class="col-md-4 chart" id="landing_chart5"></div>
						<div class="col-md-4 chart" id="landing_chart6"></div>
					</div>
				</div>
			</div>
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
	
	var graphDIV = "landing_chart";
	var graphInternalLabels = false;
	var graphLabelLength = 10;
	var graphCountField = "ir_sequence_count";
	var graphData = {!! $sample_list_json !!};

</script>

@endsection

@section('footer')
	@include('footer_detailed')
@endsection
