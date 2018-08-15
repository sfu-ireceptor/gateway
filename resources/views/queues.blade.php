@extends('template')

@section('title', 'Queues')

@section('content')
<div class="container">
	<h1>Queues</h1>
	<div class="row">
		<div class="col-md-6">

			<h2>Local jobs</h2>
			@include('queueTable', array('jobList' => $jobs['default'] ))
		</div>
		<div class="col-md-6">

			<h2>Agave notifications</h2>
			@include('queueTable', array('jobList' => $jobs['agave'] ))
		</div>
	</div>
</div>
@stop

