@extends('template')

@section('title', 'Jobs')

@section('content')
<div class="container">

	<h1>Jobs</h1>

	@if (isset($notification))
	<div class="alert alert-warning alert-dismissible" role="alert">
		<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		{!! $notification !!}
	</div>
	@endif

	@if (count($job_list_grouped_by_month) == 0)
	<p>
		<em>You haven't submitted any job yet.</em>
	</p>
	@endif

	<div class="job_list_grouped_by_month">
		@include('job/listGroupedByMonth')
	</div>
</div>
@stop

