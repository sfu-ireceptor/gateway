@extends('template')

@section('title', 'iReceptor Subscriptions')

@section('content')
<div class="container">

	<h1>iReceptor Subscription Classes</h1> 

    <p>
Sustainable funding to support <a href="https://globalbiodata.org/" class="external" target="_blank">global biodata resources</a> is challenging, in particular in <a href="https://grants.nih.gov/grants/guide/notice-files/NOT-OD-25-104.html" class="external" target="_blank">today's difficult international funding environment.</a> In order to sustain the iReceptor Gateway as a resource to the community, it is necessary for us start charging commercial users for access. Charging for commercial access will start on August 31st, 2026. <strong> Access will remain free for academic users at this time.</strong> If you are an academic user, and want to help iReceptor support open science and remain accessible to all researchers at no cost please consider donating to our <a href="https://www.gofundme.com/f/help-ireceptor-continue-lifesaving-research" class="external" target="_blank">GoFundMe initiative</a>. If you are an industry user and are interested in being a sponsor of the iReceptor mission, please reach out to iReceptor Support (support@ireceptor.org)
</p>
	<p class="subscription_intro">
You can create an iReceptor Subscription on the
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
A paid subscription (monthly or yearly) for commercial users, charged monthly.
This includes access to:
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
Users that have a Limited subscription are users that had accounts on
the iReceptor Gateway previous to our transition to commercial subscriptions,
do not have a confirmed Academic subscription (do not have a confirmed
academic email registered with their account), and have not yet purchased
a Commercial subscription. If you are such a user and are from an academic 
institution, please log in, go to your
<a href="/user/account">User Account page</a>
and change the email to use your academic email. This will trigger a
confirmation process for your Academic subscription. If you are not an
academic researcher, it will be necessary for you to purchase a Commercial
subscription from the <a href="/register">Registration page</a> to access
the iReceptor Gateway.
</p>
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

