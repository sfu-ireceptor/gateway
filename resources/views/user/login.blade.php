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
					</div>
				@endisset
			</div>

		</div>

		<div class="col-md-9">

			<div class="intro">
				<p>
					A <strong>science gateway</strong>
					that enables the discovery, analysis and download
					of <a href="https://www.antibodysociety.org/the-airr-community/">AIRR-seq data</a> (antibody/B-cell and T-cell receptor repertoires)
					<br>from multiple independent repositories					
				</p>
			</div>

			<div class="intro2">

				<p>
					Search study metadata and sequence features from
					<strong>{{ human_number($total_sequences) }} {{ str_plural('sequence', $total_sequences)}}</strong>,
					<strong>{{ $total_samples }} {{ str_plural('repertoire', $total_samples)}}</strong>, and
					<strong>{{ $total_studies }} {{ str_plural('study', $total_studies)}}</strong>
					across {{ $total_repositories }} remote {{ str_plural('repository', $total_repositories)}}.
				</p>

				<div id="charts">
					<div class="row">
						<div class="col-md-4 chart" id="chart1"></div>
						<div class="col-md-4 chart" id="chart2"></div>
						<div class="col-md-4 chart" id="chart3"></div>
					</div>
					<div class="row">
						<div class="col-md-4 chart" id="chart4"></div>
						<div class="col-md-4 chart" id="chart5"></div>
						<div class="col-md-4 chart" id="chart6"></div>
					</div>
				</div>
				
			</div>
		</div>
	</div>
</div>

<script>
	
	var graphFields = [
	        "study_type", 
	        "organism",
	        "disease_diagnosis", 
	        "tissue",
	        "pcr_target_locus",
	        "template_class"
	    ];
	
	var graphNames = [
	        "@lang('short.study_type')",
	        "@lang('short.organism')", 
	        "@lang('short.disease_diagnosis')",
	        "@lang('short.tissue')", 
	        "@lang('short.pcr_target_locus')",
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
