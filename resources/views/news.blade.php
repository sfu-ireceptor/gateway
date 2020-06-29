@extends('template')

@section('title', 'News')

@section('content')
<div class="container page">

	<h1>What's New</h1>

	<div class="row news_list">
		<div class="col-md-6">
			@foreach ($news_list as $n)
				<div class="panel panel-default beta_version2	">
					<div class="panel-heading">
						<h3 class="panel-title">{{ Carbon\Carbon::parse($n->created_at)->format('M d, Y') }}</h3>							
					</div>
					<div class="panel-body">
						{!! $n->message !!}
					</div>
				</div>
			@endforeach
		</div>
	</div>

</div>
@stop

	