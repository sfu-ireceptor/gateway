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

		<div class="col-md-5">
			{{ Form::open(array('url' => 'register', 'role' => 'form')) }}
				<div class="honey-pot">
					<label for="email">Do no fill this field, it's used to prevent spam</label>
					<input name="email" type="text" value="" id="email">
				</div>

				<div class="panel panel-default">
					<div class="panel-body">
					    <div class="form-group {{ $errors->first('first_name') ? 'has-error' : ''}}">
							{{ Form::label('first_name', 'First Name') }} <span class="error">{{ $errors->first('first_name') }}</span>
							{{ Form::text('first_name', '', array('class' => 'form-control', 'placeholder' => '')) }}
						</div>

					    <div class="form-group {{ $errors->first('last_name') ? 'has-error' : ''}}">
							{{ Form::label('last_name', 'Last Name') }} <span class="error">{{ $errors->first('last_name') }}</span>
							{{ Form::text('last_name', '', array('class' => 'form-control')) }}
						</div>

					    <div class="form-group {{ $errors->first('email') ? 'has-error' : ''}}">
							{{ Form::label('email2', 'Email (institution/company preferred)') }} <span class="error">{{ $errors->first('email2') }}</span>
							{{ Form::text('email2', '', array('class' => 'form-control')) }}
							@if ($errors->first('email2') == 'This account already exists')
								<a href="/user/forgot-password/{{ old('email2') }}">Forgot your password?</a>
							@endif
						</div>
					</div>
				</div>

				<div class="panel panel-default">
					<div class="panel-heading">
						<h3 class="panel-title">Required by our funders</h3>
					</div>
					<div class="panel-body">
					    <div class="form-group {{ $errors->first('country') ? 'has-error' : ''}}">
							{{ Form::label('country', 'Country') }} <span class="error">{{ $errors->first('country') }}</span>
							{{ Form::text('country', $country, array('class' => 'form-control')) }}
						</div>

					    <div class="form-group {{ $errors->first('institution') ? 'has-error' : ''}}">
							{{ Form::label('institution', 'Institution') }} <span class="error">{{ $errors->first('institution') }}</span>
							{{ Form::text('institution', '', array('class' => 'form-control')) }}
						</div>
					</div>
				</div>

				{{ Form::submit('Create an account', array('class' => 'btn btn-primary')) }}

			{{ Form::close() }}
		</div>

	</div>

</div>
@stop 
