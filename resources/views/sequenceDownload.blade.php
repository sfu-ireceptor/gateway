@extends('template')

@section('title', 'Sequence Download')

@section('content')
<div class="container-fluid sequence_container">

	<h1>Sequence Download</h1>

	<div class="row">		
		<div class="col-md-10">
			<p class="download_status">
				Please wait, we're preparing your data...
				<a href="/sequences-download-direct?query_id={{ $query_id }}" class="download_sequences"></a>
			</p>
		</div>
	</div>
</div>

@stop