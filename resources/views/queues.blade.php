@extends('template')

@section('title', 'Queues')

@section('content')
<div class="container">
	<h1>Queues</h1>

	<!-- Nav tabs -->
	<ul class="nav nav-tabs queues_tabs" role="tablist">
		<li role="presentation" class="active"><a href="#default" aria-controls="home" role="tab" data-toggle="tab">Jobs</a></li>
		<li role="presentation"><a href="#long" aria-controls="profile" role="tab" data-toggle="tab">Long Jobs</a></li>
		<li role="presentation"><a href="#agave" aria-controls="messages" role="tab" data-toggle="tab">Agave notifications</a></li>
		<li role="presentation"><a href="#admin" aria-controls="settings" role="tab" data-toggle="tab">Admin Jobs</a></li>
	</ul>

	<!-- Tab panes -->
	<div class="tab-content">
		<div role="tabpanel" class="tab-pane active" id="default">
			@include('queueTable', array('jobList' => $jobs['default'] ))			
		</div>
		<div role="tabpanel" class="tab-pane" id="long">
			@include('queueTable', array('jobList' => $jobs['long'] ))
		</div>
		<div role="tabpanel" class="tab-pane" id="agave">
			@include('queueTable', array('jobList' => $jobs['agave'] ))
		</div>
		<div role="tabpanel" class="tab-pane" id="admin">
			@include('queueTable', array('jobList' => $jobs['admin'] ))
		</div>
	</div>

</div>
@stop

