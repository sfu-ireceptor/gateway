@extends('template')

@section('title', 'iReceptor Subscriptions')

@section('content')
<div class="container">

	<h1>iReceptor Subscription Classes</h1> 

    <p>
<p><strong>In order to sustain the iReceptor Gateway and the iReceptor AIRR Data Commons repositories as resources to the community, as of Dec 15, 2026 iReceptor will be charging for access to the iReceptor Gateway.</strong></p> <p><strong>Why is this necessary?</strong> Sustainable funding to support <a href="https://globalbiodata.org/" class="external" target="_blank">global biodata resources</a> is challenging, in particular in <a href="https://grants.nih.gov/grants/guide/notice-files/NOT-OD-25-104.html" class="external" target="_blank">today's difficult international funding environment.</a> Biodata resources are <a href="https://doi.org/10.1093/genetics/iyag274" class="external" target="_blank">continually at existential risk due to funding challenges</a>. We are no longer able to sustain the platform through grant funding, and it is now necessary to charge for use.</p> 
</p>
	<p class="subscription_intro">
You can create an iReceptor Subscription on the
<a href="/register">Register page</a>.
For more information on using
the iReceptor Gateway please refer to the
<a href="/terms">Terms and Conditions</a>
and the
<a href="privacy-policy">Privacy Policy</a>
pages. The types of iReceptor Gateway subscriptions are listed below:</p>
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
A discounted paid subscription for academic users. Academic access is determined
based on email address. If a user has a valid academic email, they are
eligible for access to the iReceptor Gateway at the discounted rate. 
This includes access to:
</p>
<ul>
<li>Study/subject/sample metadata queries and downloads</li>
<li>Sequence annotation queries and downloads</li>
<li>Clone annotation queries and downloads</li>
<li>Cell annotation/expression queries and downloads</li>
</ul>

		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
            <h2>Subscription Status</h2>
<p>
Users that have a <strong>"Limited"</strong> subscription are users that had accounts on
the iReceptor Gateway previous to our transition to paid subscriptions and have not yet
paid for a subscription. If you want to continue to use the iReceptor Gateway,
please go to the <a href="/register">Registration</a>
page and select a subscription category. If you believe
that the limited subscription is incorrect (that you have paid for a subscription)
please contact support@ireceptor.org.
</p>
<p>
For both <strong>"Commercial"</strong> and <strong>"Academic"</strong> subscriptions, the subscription may 
temporarily only provide limited access to the iReceptor Gateway. This
would be the result of a pending approval of a change to a subscription
type (denoted as either <strong>"Academic-Approval Pending"</strong> or 
<strong>"Commercial-Approval Pending"</strong>) or the account is waiting for the user
to confirm a valid email address (denoted with a status of either
<strong>"Academic-Email Unconfirmed"</strong> or <strong>"Commercial-Email Unconfirmed"</strong>). 
You can see the status of your subscription on your
<a href="/user/account">User Account</a> page.
            </p>
        </div>
    </div>
</div>
@stop

