<!DOCTYPE html>
<html lang="en">

	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
	    <meta name="csrf-token" content="{{ csrf_token() }}">

		<title>@yield('title', 'Untitled')@yield('base_title', ' | iReceptor')</title>

		<!-- css -->
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
		<link href="/css/jstree/default/style.min.css" rel="stylesheet" />
		<link href="/css/main.css?v=2" rel="stylesheet">

		<!-- IE8 support of HTML5 elements and media queries -->
		<!--[if lt IE 9]>
		  <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
		  <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
		<![endif]-->
	</head>

	<body>
		<nav class="navbar navbar-default" role="navigation">
		<div class="container-fluid">
		    <!-- Brand and toggle get grouped for better mobile display -->
			<div class="navbar-header">
		    	<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
			    	<span class="sr-only">Toggle navigation</span>
			        <span class="icon-bar"></span>
			        <span class="icon-bar"></span>
			        <span class="icon-bar"></span>
		      	</button>
		      	<a class="navbar-brand" href="/">
					<img src="/images/logos/ireceptor_logo.png">
		      		<span>iReceptor</span>
		      	</a>
		     </div>

		    <!-- Collect the nav links, forms, and other content for toggling -->
		    <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
		      <ul class="nav navbar-nav">
		    	@if(Auth::check())
			      	<li role="presentation" class="<?= Request::is('bookmarks') ? 'active' : '' ?>"><a href="/bookmarks">Bookmarks</a></li>
			      	<li role="presentation" class="<?= Request::is('jobs') || Request::is('job/*') ? 'active' : '' ?>"><a href="/jobs">Jobs</a></li>
				@endif
		      </ul>


		      <ul class="nav navbar-nav navbar-right">
		    	@if(Auth::check())
			    	@if(Auth::user()->isAdmin())		    	
						<li class="dropdown">
							<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">admin<span class="caret"></span></a>
							<ul class="dropdown-menu" role="menu">
							  <li><a href="/admin/users">Users</a></li>
							  <li><a href="/admin/databases">Databases</a></li>
							  <li><a href="/admin/queues">Queues</a></li>
							  <li><a href="/canarie">CANARIE</a></li>
							</ul>
						</li>
					@endif
					<li class="dropdown">
						<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">{{ Auth::user()->username }}<span class="caret"></span></a>
						<ul class="dropdown-menu" role="menu">
						  <li><a href="/user/account">My account</a></li>
						  <li><a href="/systems">Systems</a></li>
						  <li><a href="/logout">Log Out</a></li>
						</ul>
					</li>
				@endif
		      </ul>
		    </div><!-- /.navbar-collapse -->
		  </div><!-- /.container-fluid -->
		</nav>

		@yield('content')

		<footer>
		<div class="container-fluid footer_container">
			<div class="container">
				<div class="row">
					<div class="col-md-12">
						<p class="text-right">
							<a href="/about">About iReceptor</a>
						</p>
					</div>
				</div>
			</div>
		</div>
		</footer>


		<!-- javascript -->
		<script src="/js/jquery.min.js"></script>
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
		
		<script src="/js/jstree.min.js"></script>
		<script src="https://code.highcharts.com/highcharts.js"></script>
		<script src="https://code.highcharts.com/modules/data.js"></script>
		<script src="https://code.highcharts.com/modules/drilldown.js"></script>
		
		<script src="/js/main.js?v=2"></script>
		<script src="/js/visualization.js?v=3"></script>
	</body>

</html>