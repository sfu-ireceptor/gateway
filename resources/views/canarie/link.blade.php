@extends('template')

@section('title', $title)

@section('content')
<div class="container">
	<div class="row">
		<div class="col-md-6">
			<h1>{{ $page }}</h1>
			<p>
				For more information, see
				<a href="{{ $url }}">{{ $url }}</a>
			</p>


		</div>
	</div>
</div>
@stop

