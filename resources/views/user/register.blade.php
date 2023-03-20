@extends('template')

@section('title', 'Register')
 
@section('content')
<div class="container">
	
	<h1>Create an account</h1>

	@if (isset($notification))
	<div class="alert alert-warning alert-dismissible" role="alert">
		<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		{!! $notification !!}
	</div>
	@endif


	<div class="row">

		<div class="col-md-4">
			<!-- <p>We'll email you a link to reset it.</p> -->
			{{ Form::open(array('url' => 'register', 'role' => 'form')) }}
			    <div class="form-group {{ $errors->first('first_name') ? 'has-error' : ''}}">
					{{ Form::label('first_name', 'First Name') }} <span class="error">{{ $errors->first('first_name') }}</span>
					{{ Form::text('first_name', '', array('class' => 'form-control', 'placeholder' => '')) }}
				</div>

			    <div class="form-group {{ $errors->first('last_name') ? 'has-error' : ''}}">
					{{ Form::label('last_name', 'Last Name') }} <span class="error">{{ $errors->first('last_name') }}</span>
					{{ Form::text('last_name', '', array('class' => 'form-control')) }}
				</div>

			    <div class="form-group {{ $errors->first('email') ? 'has-error' : ''}}">
					{{ Form::label('email', 'Email') }} <span class="error">{{ $errors->first('email') }}</span>
					{{ Form::text('email', '', array('class' => 'form-control')) }}
				</div>

				{{ Form::submit('Register', array('class' => 'btn btn-primary')) }}

			{{ Form::close() }}
		</div>

	</div>

</div>
@stop 
