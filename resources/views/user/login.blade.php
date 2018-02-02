@extends('template')

@section('title', 'Log In')
 
@section('content')
<div class="container login_container">

	<div class="row">
		<div class="col-md-3">

			<div class="login_logo">
				<img src="/images/logos/ireceptor_logo.png">
				<span>iReceptor</span>
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

				<div class="panel panel-warning beta_version">
					<div class="panel-heading">
						<h3 class="panel-title">Beta version</h3>
					</div>
					<div class="panel-body">
						<p>We are still working on performance, fine-tuning the interface and adding new data.</p>
						<p>Full release: February 2018.</p>
					</div>
				</div>
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


	<div class="login_fold_box">
	</div>

	<div class="row">
		<div class="col-md-9">
		<div class="login_about_box">
			<h4>
			About iReceptor
			</h4>
			<p>
			iReceptor is a distributed data management system and scientific gateway for mining “Next Generation”sequence data from immune responses. The goal of the project is to: improve the design of vaccines, therapeutic antibodies and cancer immunotherapies by integrating Canadian and international data repositories of antibody and T-cell receptor gene sequences.
			</p>
			<p>
			iReceptor provides a technology platform that will lower the barrier to immune genetics researchers who need to federate large, distributed, immune genetics data repositories in order to answer complex questions about the immune response. The focus of the iReceptor project is to leverage existing capabilities and technologies to build a new scientific platform for the immune genetics research community.
			</p>
		</div>
		</div>
		<div class="col-md-3">
		<div class="login_about_box">

			<h4>
			Contact/Resources
			</h4>
			<p>
				To ask question or get involved, email: support@ireceptor.org.
			</p>
			<p>
				To learn more about iReceptor visit the <a href="http://www.ireceptor.org" target="_blank">iReceptor website</a>.
			</p>
			<p>
				To learn more about the Adaptive Immune Response Repertoire (AIRR) Community, visit the
				<a href="http://www.airr-community.org" target="_blank">AIRR website</a>.
			</p>
		</div>
		</div>
	</div>
	<div class="login_fold_box">
	</div>

	<div class="row">
		<div class="col-md-4">
			<h4>Funded by</h4>
			<a href="http://www.innovation.ca" class="cfi">
				<img src="/images/logos/cfi.png"><br />
			</a>
			<a href="http://www2.gov.bc.ca/gov/content/governments/about-the-bc-government/technology-innovation/bckdf" class="bckdf">
				<img src="/images/logos/bckdf.png"><br />
			</a>
			<a href="http://www.canarie.ca" class="canarie">
				<img src="/images/logos/canarie.png">
			</a>
		</div>
		<div class="col-md-4">
			<h4>Developed and Run by</h4>
			<a href="http://www.irmacs.sfu.ca/" class="irmacs">
				<img src="/images/logos/irmacs.png">
			</a>
		</div>
		<div class="col-md-4">
			<h4 class="powered">Powered by</h4>
			<a href="https://www.computecanada.ca/" class="compute_canada">
				<img src="/images/logos/compute_canada.png">
			</a>
			<a href="http://agaveapi.co/" class="agave">
				<img src="/images/logos/agave.png">
			</a>
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

@stop
