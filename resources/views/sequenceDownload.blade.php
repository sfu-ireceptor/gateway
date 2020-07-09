@extends('template')

@section('title', 'Sequence Download')

@section('content')
<div class="container sequence_container">

	<h1>Sequence Download</h1>

	<div class="download_message">
		<div class="row">
			<div class="col-md-1">
				<div class="loader"></div> 
			</div>
			<div class="col-md-11">
				<h3>We're preparing your data...</h3>
				<p>
					This can take a while, please don't refresh or close this window.
					<a href="/sequences-download-direct?query_id={{ $query_id }}" class="download_sequences_direct"></a>
				</p>
				<p>
					<small>Time estimate: less than {{ $time_estimate_max }}</small>.
				</p>
			</div>
		</div>
	</div>

	<div class="download_status alert" role="alert">
	</div>
</div>
@stop