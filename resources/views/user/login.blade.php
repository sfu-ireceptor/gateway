@extends('template')

@section('title', 'Log In')
 
@section('content')
<div class="container">

	<div class="row">
		
		<div class="col-md-6">
			<div class="jumbotron">
				<h1>iReceptor Gateway</h1>
				<p>The iReceptor gateway is part of iReceptor, a distributed data management system for mining “Next Generation” sequence data from immune responses.</p> 
				<p>Researchers can apply for an account by sending an email to <a href="mailto:support@ireceptor.org">support@ireceptor.org</a>.</p>
			</div>
		</div>


		<div class="col-md-4 col-md-offset-1 login-box">

			{{ Form::open(array('role' => 'form')) }}			

		    <div class="text-danger">
		    	{{ $errors->first() }}
		    </div>

			<p>
				{{ Form::label('username', 'Username') }}
				{{ Form::text('username', '', array('class' => 'form-control')) }}
			</p>
			<p>
				{{ Form::label('password', 'Password') }}
				{{ $errors->first("password") }}
				{{ Form::password('password', array('class' => 'form-control')) }}
			</p>
			{{ Form::submit('Log In', array('class' => 'btn btn-primary')) }}
			<a class="forgot" href="/user/forgot-password">Forgot your password?</a>
			{{ Form::close() }}
		</div>
	</div>


</div>

<footer>
<div class="container-fluid footer_container">
	<div class="container">
	<div class="row footer">
		<div class="col-md-3">
			<h4>Funded by</h4>
			<a href="http://www.innovation.ca" class="cfi">
				<img src="/images/logos/cfi.png">
			</a>
			<a href="http://www2.gov.bc.ca/gov/content/governments/about-the-bc-government/technology-innovation/bckdf" class="bckdf">
				<img src="/images/logos/bckdf.png">
			</a>
			<a href="http://www.canarie.ca" class="canarie">
				<img src="/images/logos/canarie.png">
			</a>
		</div>
		<div class="col-md-6">
			<h4 class="powered">Powered by</h4>
			<a href="https://www.computecanada.ca/" class="compute_canada">
				<img src="/images/logos/compute_canada.png">
			</a>
			<a href="http://agaveapi.co/" class="agave">
				<img src="/images/logos/agave.png">
			</a>
		</div>
		<div class="col-md-3">
			<h4>Developed and Run by</h4>
			<a href="http://www.irmacs.sfu.ca/" class="irmacs">
				<img src="/images/logos/irmacs.png">
			</a>
		</div>
	</div>
	</div>
</footer>
</div>
@stop
