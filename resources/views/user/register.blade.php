@extends('template')

@section('title', 'Register')
 
@section('content')


@if (config('ireceptor.commercial'))
  <div class="container">
    <div class="row">

<h2>Subscribe as a Commercial User</h2>

{!! config('ireceptor.commercial_text') !!}

<script async src="https://js.stripe.com/v3/pricing-table.js"></script>
<stripe-pricing-table pricing-table-id="prctbl_1Tw5ogRy2LaOcFw53xL12gao"
publishable-key="pk_live_51TuHHfRy2LaOcFw5VMzBOcUOzpbmBEEOX2TNnRCkHpXgvz4r0PY6s6WNnkDPPl651HF86iAKBzXA1XruOg2dZh0j00h3ipoGQl">
</stripe-pricing-table>

      <div class="col-md-6">
      </div>
    </div>
  </div>
@endif

<div class="container">
	
@if (config('ireceptor.commercial'))
	<h2>Create a free Academic Account</h2>
@else
	<h2>Create an Account</h2>
@endif

        @if (isset($notification))
	<div class="alert alert-warning alert-dismissible" role="alert">
		<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		{!! $notification !!}
	</div>
	@endif


	<div class="row">

		<div class="col-md-6">
			{{ Form::open(array('url' => 'register', 'role' => 'form')) }}
				<div class="honey-pot">
					<label for="email">Do no fill this field, it's used to prevent spam</label>
					<input name="email" type="text" value="" id="email">
				</div>
@if (config('ireceptor.commercial'))
                                <div>
{!! config('ireceptor.academic_text') !!}
                                </div>
@endif
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
@if (config('ireceptor.commercial'))
							{{ Form::label('email2', 'Email (Academic Institutional Email required)') }} <span class="error">{{ $errors->first('email2') }}</span>
@else
							{{ Form::label('email2', 'Email (Academic Institutional Email recommended if available)') }} <span class="error">{{ $errors->first('email2') }}</span>
@endif

							{{ Form::text('email2', '', array('class' => 'form-control')) }}
							@if ($errors->first('email2') == 'This account already exists')
								<a href="/user/forgot-password/{{ old('email2') }}">Forgot your password?</a>
							@endif
						</div>
					    <div class="form-group {{ $errors->first('country') ? 'has-error' : ''}}">
							{{ Form::label('country', 'Country') }} <span class="error">{{ $errors->first('country') }}</span>
							{{ Form::text('country', $country, array('class' => 'form-control')) }}
						</div>

					    <div class="form-group {{ $errors->first('institution') ? 'has-error' : ''}}">
							{{ Form::label('institution', 'Institution') }} <span class="error">{{ $errors->first('institution') }}</span>
							{{ Form::text('institution', '', array('class' => 'form-control')) }}
						</div>

					    <div class="form-group {{ $errors->first('notes') ? 'has-error' : ''}}">
							{{ Form::label('notes', 'Tell us about yourself and your interest in the iReceptor Gateway') }} <span class="error">{{ $errors->first('notes') }}</span>
							{{ Form::textarea('notes', '', array('class' => 'form-control')) }}
						</div>

					</div>
				</div>

@if (config('ireceptor.commercial'))
				{{ Form::submit('Create your free Academic Account', array('class' => 'btn btn-primary')) }}
@else
				{{ Form::submit('Create Account', array('class' => 'btn btn-primary')) }}
@endif

			{{ Form::close() }}
		</div>

	</div>

</div>
@stop 
