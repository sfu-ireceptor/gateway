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
				<a class="btn btn-default btn-lg" role="button" href="/sequences?cols=3_65_26_6_10_64_113&amp;filters_order=64&amp;cdr3region_sequence_aa=&amp;add_field=cdr3_length">Quick CDR3 Search →</a>
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
var graphData = {!! $sample_list_json !!};
</script>

@stop

	