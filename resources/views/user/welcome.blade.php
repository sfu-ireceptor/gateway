@extends('template')

@section('title', 'Welcome')
 
@section('content')
<div class="container">
	
	<h1>Welcome to iReceptor</h1>

	<div class="row">

		<div class="col-md-5">
			<p>Your account has been successfully created, and we already logged you in. <strong>You will receive an email shortly</strong> with your username and password.</p>

			<p><a href="https://ireceptor.org/platform/doc">Documentation on how to use the site</a></p>
	  
			<p><a href="mailto:support@ireceptor.org">Let us know</a> if you have questions, problems, or feedback.</p>

			<p class="button_botttom_container">
				<a role="button" class="btn btn-primary"  href="/home">
					Proceed to home page →
				</a>
			</p>

		</div>

	</div>

</div>
@stop 
