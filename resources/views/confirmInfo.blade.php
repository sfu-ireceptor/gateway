@extends('template')

@section('title', 'iReceptor Info Confirmation')

@section('content')
<div class="container">
	<div class="confirm_message">
		<p>Dear iReceptor User,</p>

<p>
We have changed our <a href="/terms">Terms and Conditions</a> and 
<a href="/privacy-policy">Privacy Policy</a>. Please review these changes
and click the button below to acknowledge that you agree to adhere
to these terms and policies. 
</p>

<p>
Once you click on the button below, you will be sent a 
confirmation link to the email associated with this account. Please follow
that link to enable your account on the iReceptor Gateway.
</p>

<p>
Your account has temporarily been assigned reduced access on the iReceptor Gateway. Once you have clicked on the confirmation link, you will be given access according to the status of your iReceptor Gateway subscription. You can find your subscription level at your <a href=/user/account>user account page</a>.
</p>

<p>
If the email associated with your iReceptor Gateway account is incorrect, you can go to your <a href=/user/account>user account page</a> to change the email. 
</p>

		<p>
			Sincerely,<br>
			The iReceptor team
		</p>

		<div class="announcement" role="alert">
			<p>
				<a  class="btn btn-success"  role="button" href="/confirm-info-ok">I agree to these Terms and Policies</a><br>
			</p>
		</div>
	</div>
</div>
@stop


