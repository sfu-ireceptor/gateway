@extends('template')

@section('title', 'Add News')

@section('content')
<div class="container">
	
	<h1>Add News</h1>

	@if (isset($notification))
	<div class="alert alert-warning alert-dismissible" role="alert">
		<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		{!! $notification !!}
	</div>
	@endif


	<div class="row">

		<div class="col-md-4">
			{{ Form::open(array('url' => 'admin/add-news', 'role' => 'form')) }}

			    <div class="form-group {{ $errors->first('message') ? 'has-error' : ''}}">
					{{ Form::label('message', 'Message') }} <span class="error">{{ $errors->first('message') }}</span>
					{{ Form::textarea('message', '', array('class' => 'form-control', 'placeholder' => '')) }}
				</div>

				{{ Form::submit('Add', array('class' => 'btn btn-primary')) }}

			{{ Form::close() }}
		</div>

	</div>

</div>
@stop