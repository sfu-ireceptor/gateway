@extends('template')

@section('title', 'Log In')
 
@section('content')
<div class="container login_container">

	<div class="row">
		<div class="col-md-10">
			<div class="intro">
				<p><strong>iReceptor</strong> provides access to “Next Generation” sequence data from immune responses: <span class="stats_total_sequences">millions of </span> sequences, from 5 distributed curated databases, are available for mining and download.</p> 
			</div>
		</div>
	</div>

	<div class="row">		
		<div class="col-md-9">
			<div id="landing_charts">
				<div class="row">
					<div class="col-md-3 chart" id="landing_chart1"></div>
					<div class="col-md-3 chart" id="landing_chart2"></div>
					<div class="col-md-3 chart" id="landing_chart3"></div>
				</div>
				<div class="row">
					<div class="col-md-3 chart" id="landing_chart4"></div>
					<div class="col-md-3 chart" id="landing_chart5"></div>
					<div class="col-md-3 chart" id="landing_chart6"></div>
				</div>
			</div>
		</div>


		<div class="col-md-3 login-box">
			<div class="panel panel-default">
				<div class="panel-body">
					{{ Form::open(array('role' => 'form')) }}
						<div class="text-danger">
							{{ $errors->first() }}
							@if ($errors->first())
								<p><a class="forgot" href="/user/forgot-password">Forgot your username or password?</a></p>
							@endif
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
						<p class="submit">
							{{ Form::submit('Log In →', array('class' => 'btn btn-primary')) }}
						</p>					
					{{ Form::close() }}
					<p>Researchers can apply for an account by sending an email to <a href="mailto:support@ireceptor.org">support@ireceptor.org</a>.</p>
				</div>
			</div>

{{-- 			<div class="panel panel-default">
				<div class="panel-heading">
				 	<h3 class="panel-title">Need an account?</h3>
				</div>
				<div class="panel-body">
					{{ Form::open(array('role' => 'form')) }}
					    <div class="text-danger">
					    	{{ $errors->first() }}
					    </div>

						<p>
							{{ Form::label('username', 'Last Name') }}
							{{ Form::text('username', '', array('class' => 'form-control')) }}
						</p>
						<p>
							{{ Form::label('username', 'First Name') }}
							{{ Form::text('username', '', array('class' => 'form-control')) }}
						</p>

						<p>
							{{ Form::label('username', 'Email') }}
							{{ Form::text('username', '', array('class' => 'form-control')) }}
						</p>
						<p class="submit">
							{{ Form::submit('Request an account', array('class' => 'btn btn-primary')) }}
						</p>					
					{{ Form::close() }}
				</div>
			</div> --}}


		</div>



	</div>


</div>
</div>
@stop
