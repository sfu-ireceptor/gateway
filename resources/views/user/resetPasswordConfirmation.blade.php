@extends('template')

@section('title', 'Your password has been reset')
 
@section('content')
<div class="container">
	
	<h1>Your password has been reset</h1>

	<div class="row">

		<div class="col-md-6">
			<p>We emailed you the new password. But no need to enter it right now, we logged you in :)</p>
			<p><a href="/home">Go to home page.</a></p>
		</div>

	</div>

</div>
@stop 
