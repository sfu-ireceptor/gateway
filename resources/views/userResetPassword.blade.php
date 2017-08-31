@extends('template')

@section('title', 'Reset Password')
 
@section('content')
<div class="container">
	
	<h1>Reset Password</h1>

	@if (isset($notification))
	<div class="alert alert-warning alert-dismissible" role="alert">
		<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		{!! $notification !!}
	</div>
	@endif


	<div class="row">

		<div class="col-md-4">
			{{ Form::open(array('url' => 'user/reset-password', 'role' => 'form')) }}

			    <div class="form-group {{ $errors->first('current_password') ? 'has-error' : ''}}">
					{{ Form::label('current_password', 'Current Password') }} <span class="error">{{ $errors->first('current_password') }}</span>
					{{ Form::password('current_password', array('class' => 'form-control', 'placeholder' => '')) }}
				</div>
				

				{{ Form::submit('Reset password', array('class' => 'btn btn-primary')) }}

			{{ Form::close() }}
		</div>

	</div>

</div>
@stop
