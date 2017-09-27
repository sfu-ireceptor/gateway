@extends('template')

@section('title', 'Home')

@section('content')
<div class="container">

<div class="jumbotron">
	<h1>Welcome to iReceptor</h1>
	<p id="ad">iReceptor federates searches and downloads over <span id="landing_summary"> a billion sequences</span>.</p>

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

<div class="row">
	<div class="col-md-5">
		<p>
			<a class="btn btn-default btn-lg" role="button" href="/sequences?cols=3_65_26_6_10_64_113&amp;filters_order=64&amp;cdr3region_sequence_aa=&amp;add_field=cdr3_length">Quick CDR3 Region Search</a>
		</p>
		<p>Search for sequences by CDR3 sequence</p>
	</div>

	<div class="col-md-4">
		<p>	
			<a  class="btn btn-default btn-lg" role="button" href="/samples">Advanced Search</a>
		</p>
		<p>Search for sequences via samples</p>
	</div>
</div>

</div>
@stop

	