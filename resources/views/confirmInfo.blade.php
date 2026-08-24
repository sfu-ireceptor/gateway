@extends('template')

@section('title', 'iReceptor Info Confirmation')

@section('content')
<div class="container">
	<div class="confirm_message">
		<p>Dear iReceptor User,</p>

<p>
As of August 31st, 2026, the iReceptor Gateway is using subscription 
levels to control access to the services provided by the gateway. See our
<a ref="/subscriptions">Subscriptions page</a> for more details.

There are currently two levels of subscription, a free Academic subscription and
a paid Commercial subscription. If the email you use for your account is from
a recognized academic institution, you will automatically be granted
an Academic subscription.
If you have paid for a Commercial subscription, you will have a Commercial
subscription. If neither of these apply to you, you will temporarily be
assigned a Limited subscription, allowing you to either apply for an Academic
subscription (by changing your account email to use your academic email)
or pay for a Commercial subscription using the <a href="/register">Registration page</a>.
<p>
Your current subscription level is: <b>{{ $status }}</b>
<p>
We have also changed our <a href="/terms">Terms and Conditions</a> and 
<a href="/privacy-policy">Privacy Policy</a>. Please review these changes.
<p>
To confirm your email address and acknowledge that you agree to adhere 
to these terms and policies, please click on the button below. 
Your account has temporarily been assigned reduced access on the
iReceptor Gateway. Once you have clicked on the email confirmation link,
you will be given
access according to the status of your iReceptor Gateway subscription.
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


