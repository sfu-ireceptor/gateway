@extends('template')

@section('title', $name)

@section('content')
<div class="container">
	<h1>{{ $name }}</h1>
	
	<div class="row">
		<div class="col-md-6">

			@if (isset($category))
				<h2>Category</h2>
				{{ $category }}
			@endif

			<h2>Synopsis</h2>
			{{ $synopsis }}

			<h2>Version</h2>
			{{ $version }}

			<h2>Institution</h2>
			{{ $institution }}

			<h2>Release Time</h2>
			{{ $releaseTime }}

			<h2>Research Subject</h2>
			{{ $researchSubject }}

			<h2>Support Email</h2>
			{{ $supportEmail }}

			<h2>Tags</h2>
			{{ implode(',', $tags) }}

		</div>
	</div>

</div>
@stop

