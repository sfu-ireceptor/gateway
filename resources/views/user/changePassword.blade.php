@extends('template')

@section('title', 'Change password')

@section('content')
<div class="container">
	
	<h1>Change password</h1>

	@if (isset($notification))
	<div class="alert alert-warning alert-dismissible" role="alert">
		<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		{!! $notification !!}
	</div>
	@endif


	<div class="row change_password">

		<div class="col-md-4">
			{{ Form::open(array('url' => 'user/change-password', 'role' => 'form')) }}

			    <div class="form-group {{ $errors->first('current_password') ? 'has-error' : ''}}">
					{{ Form::label('current_password', 'Current Password') }} 
					<a class="forgot" href="/user/forgot-password">Forgot your password?</a>
					<span class="error">{{ $errors->first('current_password') }}</span>
					{{ Form::password('current_password', array('class' => 'form-control', 'placeholder' => '')) }}
				</div>
				
			    <div class="form-group {{ $errors->first('password') ? 'has-error' : ''}}">
					{{ Form::label('password', 'New password') }} <span class="error">{{ $errors->first('password') }}</span>
					{{ Form::password('password', array('class' => 'form-control', 'placeholder' => '')) }}
				</div>

			    <div class="form-group {{ $errors->first('password_confirmation') ? 'has-error' : ''}}">
					{{ Form::label('password_confirmation', 'Confirm new password') }} <span class="error">{{ $errors->first('password_confirmation') }}</span>
					{{ Form::password('password_confirmation', array('class' => 'form-control')) }}
				</div>


				{{ Form::submit('Update password', array('class' => 'btn btn-primary')) }}

			{{ Form::close() }}
		</div>

	</div>

</div>
@stop