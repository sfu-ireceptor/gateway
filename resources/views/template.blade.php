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
		<link href="//cdn.datatables.net/1.10.16/css/jquery.dataTables.css" rel="stylesheet" type="text/css">
		<link href="/css/main.css?v=5" rel="stylesheet">

		<!-- IE8 support of HTML5 elements and media queries -->
		<!--[if lt IE 9]>
		  <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
		  <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
		<![endif]-->
	</head>

	<body>
    	@if(Auth::check())
			<nav class="navbar navbar-default" role="navigation">
			  <div class="container-fluid">
			    <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
			      <ul class="nav navbar-nav">
					<li role="presentation">
				      	<a class="navbar-brand" href="/" class="active">
							<img src="/images/logos/ireceptor_logo.png">
				      		<span>iReceptor</span>
				      	</a>
					</li>
			    	@if(Request::is('sequences-quick-search*'))
			    		<li role="presentation" class="dropdown active search">
							<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
								Search
								<span class="caret"></span>
							</a>
							<ul class="dropdown-menu" role="menu">
							  <li><a href="/sequences-quick-search">Sequence Search</a></li>
							  <li><a href="/samples">Metadata Search</a></li>
							</ul>
						</li>
						<li role="presentation" class="active sequences">
							<a href="/samples" class="active inactive">Sequences</a>
						</li>
			    	@elseif(Request::is('samples*'))
						<li role="presentation" class="dropdown active search">
							<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
								Search
								<span class="caret"></span>
							</a>
							<ul class="dropdown-menu" role="menu">
							  <li><a href="/sequences-quick-search">Sequence Search</a></li>
							  <li><a href="/samples">Metadata Search</a></li>
							</ul>
						</li>
						<li role="presentation" class="active samples">
							<a href="#" class="active inactive">
								Metadata
							</a>
						</li>
						<li role="presentation" class="sequences">
							<a href="#" class="inactive">Sequences</a>
						</li>
					@elseif(Request::is('sequences*'))
						<li role="presentation" class="dropdown active search">
							<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
								Search
								<span class="caret"></span>
							</a>
							<ul class="dropdown-menu" role="menu">
							  <li><a href="/sequences-quick-search">Sequence Search</a></li>
							  <li><a href="/samples">Metadata Search</a></li>
							</ul>
						</li>
						<li role="presentation" class="active samples">
							<a href="/samples?query_id=@yield('sample_query_id', '')">
								Metadata
							</a>
						</li>
						<li role="presentation" class="active sequences">
							<a href="#" class="active inactive">Sequences</a>
						</li>
					@else
						<li class="dropdown">
							<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">Search<span class="caret"></span></a>
							<ul class="dropdown-menu" role="menu">
							  <li><a href="/sequences-quick-search">Sequence Search</a></li>
							  <li><a href="/samples">Metadata Search</a></li>
							</ul>
						</li>
					@endif
			      </ul>

			      <ul class="nav navbar-nav navbar-right">
			    	@if(Auth::check())
				    	@if(Auth::user()->isAdmin())		    	
							<li class="dropdown">
								<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">admin<span class="caret"></span></a>
								<ul class="dropdown-menu" role="menu">
								  <li><a href="/admin/databases">Databases</a></li>
								  <li><a href="/admin/users">Users</a></li>
								  <li><a href="/admin/queries">User queries</a></li>
								  <li role="separator" class="divider"></li>
								  <li><a href="/admin/queues">Queues</a></li>
								  <li role="separator" class="divider"></li>
								  <li><a href="/admin/field-names">Field names</a></li>
								  <li><a href="/canarie">CANARIE</a></li>
								</ul>
							</li>
						@endif
						<li class="dropdown">
							<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">{{ Auth::user()->username }}<span class="caret"></span></a>
							<ul class="dropdown-menu" role="menu">
								<li><a href="/user/account">My account</a></li>
								  <li role="separator" class="divider"></li>
								<li><a href="/bookmarks">Bookmarks</a></li>
								<li><a href="/jobs">Jobs</a></li>
								<li><a href="/systems">Systems</a></li>
								  <li role="separator" class="divider"></li>
								<li><a href="/logout">Log Out</a></li>
							</ul>
						</li>
					@endif
			      </ul>
			    </div><!-- /.navbar-collapse -->
			  </div><!-- /.container-fluid -->
			</nav>
		@endif


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
		<script src="https://code.highcharts.com/modules/no-data-to-display.js"></script>  
		<script type="text/javascript" charset="utf8" src="//cdn.datatables.net/1.10.16/js/jquery.dataTables.js"></script>

		<script src="/js/main.js?v=5"></script>
		<script src="/js/visualization.js?v=5"></script>
	</body>

</html>