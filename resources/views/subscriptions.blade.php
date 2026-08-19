@extends('template')

@section('title', 'iReceptor Subscriptions')

@section('content')
<div class="container">

	<h1>iReceptor Subscription Classes</h1>

	<p class="subscription_intro">
The iReceptor Gateway uses subscriptions to manage access to the
capabilities of the gateway. You can create an iReceptor Subscription on the
<a href="/register">Register page</a>.
For more information on using
the iReceptor Gateway please refer to the
<a href="/terms">Terms and Conditions</a>
and the
<a href="privacy-policy">Privacy Policy</a>
pages.
	</p>
    <p> The types of iReceptor Gateway subscriptions are listed below:</p>
	<div class="row">
		<div class="col-md-6 commercial">
           <h2>Commercial Subscription</h2>
<p>
A paid subscription for commercial users, charged monthly, for either
the term of either a year or a month. This includes access to:
</p>
<ul>
<li>Study/subject/sample metadata queries and downloads</li>
<li>Sequence annotation queries and downloads</li>
<li>Clone annotation queries and downloads</li>
<li>Cell annotation/expression queries and downloads</li>
</ul>
		</div>
		<div class="col-md-6 academic">
           <h2>Academic Subscription</h2>
<p>
A free subscription for academic users. Academic access is determined
based on email address. If a user has a valid academic email, they are
eligible for access to the iReceptor Gateway at no charge. 
This includes access to:
</p>
<ul>
<li>Study/subject/sample metadata queries and downloads</li>
<li>Sequence annotation queries and downloads</li>
<li>Clone annotation queries and downloads</li>
<li>Cell annotation/expression queries and downloads</li>
<li>Analysis jobs on sequence, clone, and cell data</li>
</ul>

		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
            <h2>Subscription Status</h2>
            <p>
For both Commercial and Academic subscriptions, the subscription may 
temporarily only provide limited access to the iReceptor Gateway. This
would be the result of a pending approval of a change to a subscription
type (denoted as either "Academic-Approval Pending" or 
"Commercial-Approval Pending") or the account is waiting for the user
to confirm a valid email address (denoted with a status of either
"Academic-Email Unconfirmed" or "Commercial-Email Unconfirmed"). 
You can see the status of your subscription on your
<a href="/user/account">User Account</a> page.
            </p>
        </div>
    </div>
</div>
@stop

