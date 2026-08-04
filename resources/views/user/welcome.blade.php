@extends('template')

@section('title', 'Welcome')
 
@section('content')
<div class="container">
	
	<h1>Welcome to iReceptor</h1>

	<div class="row">

		<div class="col-md-5">
			<p>Your account has been successfully created, and we have logged you in with limited access as we review and approve your request for an Academic Subscription. You can view the status of your iReceptor Subscription at the <a href="/user/account">User Account page</a>. You will receive an email notification when your Academic Subscription has been approved.</p>
                        <p>You will receive an email shortly with your username and password, please use the <a href="/user/account">User Account page</a> to change your password.</p>

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
