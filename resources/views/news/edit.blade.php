@extends('template')

@section('title', 'Edit news' . $n->id)

@section('content')
<div class="container">
	
	<h1>News from {{ Carbon\Carbon::parse($n->created_at)->format('M d, Y') }}</h1>

	@if (isset($notification))
	<div class="alert alert-warning alert-dismissible" role="alert">
		<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		{!! $notification !!}
	</div>
	@endif


	<div class="row">

		<div class="col-md-4">
			{{ Form::open(array('url' => 'admin/edit-news', 'role' => 'form')) }}
				<input type="hidden" name="id" value="{{ $n->id }}">

			    <div class="form-group {{ $errors->first('message') ? 'has-error' : ''}}">
					{{ Form::label('message', 'Message') }} <span class="error">{{ $errors->first('message') }}</span>
					{{ Form::textarea('message', $n->message, array('class' => 'form-control', 'placeholder' => '')) }}
				</div>

				{{ Form::submit('Save', array('class' => 'btn btn-primary')) }}

			{{ Form::close() }}
		</div>

	</div>

</div>
@stop