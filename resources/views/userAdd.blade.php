@extends('template')

@section('title', 'Add User')

@section('content')
<div class="container">
	
	<h1>Add User</h1>

	@if (isset($notification))
	<div class="alert alert-warning alert-dismissible" role="alert">
		<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		{{ $notification }}
	</div>
	@endif


	<div class="row">

		<div class="col-md-4">
			{{ Form::open(array('url' => 'admin/add-user', 'role' => 'form')) }}

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
					{{ Form::text('email', Request::old('email'), array('class' => 'form-control')) }}
				</div>

				{{ Form::submit('Add User', array('class' => 'btn btn-primary')) }}

			{{ Form::close() }}
		</div>

	</div>

</div>
@stop