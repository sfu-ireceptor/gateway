@extends('template')

@section('title', 'Queues')

@section('content')
<div class="container">
	<h1>Queues</h1>

	<!-- Nav tabs -->
	<ul class="nav nav-tabs queues_tabs" role="tablist">
		<li role="presentation" class="active"><a href="#admin" aria-controls="settings" role="tab" data-toggle="tab">Admin</a></li>
		<li role="presentation"><a href="#short-downloads" aria-controls="home" role="tab" data-toggle="tab">Short Downloads</a></li>
		<li role="presentation"><a href="#long-downloads" aria-controls="profile" role="tab" data-toggle="tab">Long Downloads</a></li>
		<li role="presentation"><a href="#short-analysis-jobs" aria-controls="messages" role="tab" data-toggle="tab">Short Analyses</a></li>
		<li role="presentation"><a href="#long-analysis-jobs" aria-controls="settings" role="tab" data-toggle="tab">Long Analyses</a></li>
		<li role="presentation"><a href="#agave-notifications" aria-controls="settings" role="tab" data-toggle="tab">Agave Notifications</a></li>
	</ul>

	<!-- Tab panes -->
	<div class="tab-content">
		<div role="tabpanel" class="tab-pane active" id="admin">
			@include('queueTable', array('jobList' => $jobs['admin'] ))
		</div>
		<div role="tabpanel" class="tab-pane" id="short-downloads">
			@include('queueTable', array('jobList' => $jobs['short-downloads'] ))
		</div>
		<div role="tabpanel" class="tab-pane" id="long-downloads">
			@include('queueTable', array('jobList' => $jobs['long-downloads'] ))
		</div>
		<div role="tabpanel" class="tab-pane" id="short-analysis-jobs">
			@include('queueTable', array('jobList' => $jobs['short-analysis-jobs'] ))
		</div>
		<div role="tabpanel" class="tab-pane active" id="long-analysis-jobs">
			@include('queueTable', array('jobList' => $jobs['long-analysis-jobs'] ))
		</div>
		<div role="tabpanel" class="tab-pane active" id="agave-notifications">
			@include('queueTable', array('jobList' => $jobs['agave-notifications'] ))
		</div>
	</div>

</div>
@stop

