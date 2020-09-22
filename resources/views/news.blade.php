@extends('template')

@section('title', 'News')

@section('content')
<div class="container page">

	<h1>What's New</h1>
	<div class="row">
		<div class="col-md-7">
			@foreach ($news_list as $n)
			<div class="row news_item">
				<div class="col-md-12">
					<p class="news_date">{{ Carbon\Carbon::parse($n->created_at)->format('M d, Y') }}</p>						
					{!! $n->message !!}
				</div>
			</div>
			@endforeach
		</div>
		<div class="col-md-1">		
		</div>
		<div class="col-md-4">		
			<div class="panel panel-default">
			  <div class="panel-heading">
			    <h3 class="panel-title">Twitter</h3>
			  </div>
			  <div class="panel-body">
				<a href="https://twitter.com/ireceptorgw">Follow us on Twitter</a>
				to keep up with the latest news.
			  </div>
			</div>
		</div>
	</div>

</div>
@stop

	