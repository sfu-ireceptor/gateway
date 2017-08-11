@extends('template')

@section('title', $name)

@section('content')
<div class="container">
	<h1>{{ $name }}</h1>
	
	<div class="row">
		<div class="col-md-6">
			<h2>{{ $key }}</h2>
			{{ $val }}

			<h2>Last Reset</h2>
			{{ $lastReset }}
		</div>
	</div>

</div>
@stop

