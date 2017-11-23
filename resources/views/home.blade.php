@extends('template')

@section('title', 'Home')

@section('content')
<div class="container home_container">

	<div class="row">
		<div class="col-md-12">
			<p class="intro">
				iReceptor provides access to
				{{ number_format($total_sequences) }} sequences from
				{{ $total_repositories }} remote repositories,
				{{ $total_labs }} research labs,
				{{ $total_projects }} studies, and
				{{ $total_samples }} samples.
			</p>
		</div>
	</div>

	<div class="row">
		<div class="col-md-9">
			<div id="landing_charts">
				<div class="row">
					<div class="col-md-4 chart" id="landing_chart1"></div>
					<div class="col-md-4 chart" id="landing_chart2"></div>
					<div class="col-md-4 chart" id="landing_chart3"></div>
				</div>
			</div>
		</div>
		<div class="col-md-3 side_search_links">
			<p class="quick_search_link">
				<a class="btn btn-default btn-lg" role="button" href="/sequences-quick-search">Quick CDR3 Search →</a>
			</p>
			<p>Search for sequences by CDR3 sequence</p>
		</div>
	</div>
	<div class="row">
		<div class="col-md-9">
			<div id="landing_charts">	
				<div class="row">
					<div class="col-md-4 chart" id="landing_chart4"></div>
					<div class="col-md-4 chart" id="landing_chart5"></div>
					<div class="col-md-4 chart" id="landing_chart6"></div>
				</div>
			</div>
		</div>
		<div class="col-md-3 side_search_links">
			<p class="adv_search_link">	
				<a  class="btn btn-default btn-lg" role="button" href="/samples">Advanced Search →</a>
			</p>
			<p>Search for sequences via samples</p>
		</div>
	</div>
	<div class="data_container_box">
	</div>

	<div class="row">
		<div class="col-md-9">
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
		<div class="col-md-3">
			<h4>
			Contact/Resources
			</h4>
			<p>
				To ask question or get involved, email: support@ireceptor.org
			</p>
			<p>
				To learn more about iReceptor visit the <a href="http://www.ireceptor.org" target="_blank">iReceptor website</a>.
			</p>
			<p>
				To learn more about the Adaptive Immune Response Repertoire (AIRR) Community, visit the
				<a href="http://www.airr-community.org" target="_blank">AIRR website</a>
			</p>
		</div>
	</div>
	<div class="data_container_box">
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
var graphCountField = "ir_sequence_count";
var graphData = {!! $sample_list_json !!};
</script>

@stop

	