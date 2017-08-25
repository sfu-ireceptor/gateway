@extends('template')

@section('title', 'Home')

@section('content')
<div class="container">

<div class="jumbotron">
  <h1>Welcome to iReceptor</h1>
  <p>iReceptor provides searches and downloads over a billion of sequences.</p>
</div>


<div class="row">
<div class="col-md-12">

<!-- A DIV section for putting the header information. -->
<center>
<div id="header"></div>
</center>

<!--
Our container structure for the charts, a 2x3 table. Note the layout of the  
HTML can change arbitrarily and this will not effect the code. Chart 1 through
6 will go in container 1 through 6 no matter where they are. 
-->

<center>
<table>
<!-- This section not used as we use the DIV section above for summary stats.
<tr>
<td>
<div id="labs" style="min-width: 300px; max-width: 300px; margin: 0 auto"></div>
</td>
<td>
<div id="subjects" style="min-width: 300px; max-width: 300px; margin: 0 auto"></div>
</td>
<td>
<div id="samples" style="min-width: 300px; max-width: 300px; margin: 0 auto"></div>
</td>
</tr>
<tr>
-->

<td>
<div id="container1" style="min-width: 300px; height: 300px; max-width: 300px; margin: 0 auto"></div>
</td>
<td>
<div id="container2" style="min-width: 300px; height: 300px; max-width: 300px; margin: 0 auto"></div>
</td>
<td>
<div id="container3" style="min-width: 300px; height: 300px; max-width: 300px; margin: 0 auto"></div>
</td>
</tr>
<tr>
<td>
<div id="container4" style="min-width: 300px; height: 300px; max-width: 300px; margin: 0 auto"></div>
</td>
<td>
<div id="container5" style="min-width: 300px; height: 300px; max-width: 300px; margin: 0 auto"></div>
</td>
<td>
<div id="container6" style="min-width: 300px; height: 300px; max-width: 300px; margin: 0 auto"></div>
</td>
</tr>
</table>
</center>

<!-- A debug section for debug output -->
<p id="debug"></p>


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

	