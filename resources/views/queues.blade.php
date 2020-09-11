@extends('template')

@section('title', 'Queues')

@section('content')
<div class="container">
	<h1>Queues</h1>
	<div class="row">
		<div class="col-md-6">
			<h2>Jobs</h2>
			@include('queueTable', array('jobList' => $jobs['default'] ))
		</div>
		<div class="col-md-6">
			<h2>Long Jobs</h2>
			@include('queueTable', array('jobList' => $jobs['long'] ))
		</div>
	</div>
	<div class="row">
		<div class="col-md-6">
			<h2>Agave notifications</h2>
			@include('queueTable', array('jobList' => $jobs['agave'] ))
		</div>
		<div class="col-md-6">
			<h2>Admin Jobs</h2>
			@include('queueTable', array('jobList' => $jobs['admin'] ))
		</div>
	</div>
</div>
@stop

