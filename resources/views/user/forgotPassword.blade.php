@extends('template')

@section('title', 'Reset Password')
 
@section('content')
<div class="container">
	
	<h1>Forgot your password?</h1>

	@if (isset($notification))
	<div class="alert alert-warning alert-dismissible" role="alert">
		<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		{!! $notification !!}
	</div>
	@endif


	<div class="row">

		<div class="col-md-4">
			<p>No worries, we can email you a link to reset it.</p>
			{{ Form::open(array('url' => 'user/forgot-password', 'role' => 'form')) }}
			    <div class="form-group {{ $errors->first('current_password') ? 'has-error' : ''}}">
					{{ Form::label('email', 'Email') }} <span class="error">{{ $errors->first('email') }}</span>
					{{ Form::text('email', '',array('class' => 'form-control', 'placeholder' => '')) }}
				</div>
				

				{{ Form::submit('Send email', array('class' => 'btn btn-primary')) }}

			{{ Form::close() }}
		</div>

	</div>

</div>
@stop 
