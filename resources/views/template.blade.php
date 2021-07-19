<!DOCTYPE html>
<html lang="en">

	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
	    <meta name="csrf-token" content="{{ csrf_token() }}">

		<title>@yield('title', 'Untitled')@yield('base_title', ' | iReceptor')</title>

		<!-- css -->
		<link rel="stylesheet" href="/css/bootstrap.min.css" />
		<link href="/css/bootstrap-multiselect.css" rel="stylesheet" />
		<link href="/css/jstree/default/style.min.css" rel="stylesheet" />
		<link href="/css/main.css?v=65" rel="stylesheet" />

		<!-- IE8 support of HTML5 elements and media queries -->
		<!--[if lt IE 9]>
		  <script src="https://cdnjs.cloudflare.com/ajax/libs/html5shiv/3.7.3/html5shiv.min.js"></script>
		  <script src="https://cdnjs.cloudflare.com/ajax/libs/respond.js/1.4.2/respond.min.js"></script>
		<![endif]-->
	</head>

	<body class="{{ Auth::check() ? 'logged-in' : 'not-logged-in'}}">
		@if (! isset($is_login_page))
			<nav class="navbar navbar-default" role="navigation">
			  <div class="container-fluid">
			    <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
			      <ul class="nav navbar-nav">
					<li role="presentation">
				      	<a class="navbar-brand" href="/" class="active">
							<img src="/images/logos/ireceptor.png">
				      	</a>
					</li>

			    	@if(Auth::check())
				    	@if(Request::is('sequences-quick-search*'))
				    		<li role="presentation" class="dropdown active search">
								<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
									Search
									<span class="caret"></span>
								</a>
								<ul class="dropdown-menu" role="menu">
								  <li><a href="/sequences-quick-search">Sequence Quick Search</a></li>
								  <li><a href="/samples">Repertoire Metadata Search</a></li>
								</ul>
							</li>
							<li role="presentation" class="active sequences">
								<a href="/samples" class="active inactive">Sequence Quick Search</a>
							</li>
				    	@elseif(Request::is('samples*'))
							<li role="presentation" class="dropdown active search">
								<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
									Search
									<span class="caret"></span>
								</a>
								<ul class="dropdown-menu" role="menu">
								  <li><a href="/sequences-quick-search">Sequence Quick Search</a></li>
								  <li><a href="/samples">Repertoire Metadata Search</a></li>
								</ul>
							</li>
							<li role="presentation" class="active samples">
								<a href="#" class="active inactive">
									1. Repertoire Metadata
								</a>
							</li>
							<li role="presentation" class="sequences">
								<a href="#" class="inactive">2. Sequences/Clones</a>
							</li>
						@elseif(Request::is('sequences*'))
							<li role="presentation" class="dropdown active search">
								<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
									Search
									<span class="caret"></span>
								</a>
								<ul class="dropdown-menu" role="menu">
								  <li><a href="/sequences-quick-search">Sequence Quick Search</a></li>
								  <li><a href="/samples">Repertoire Metadata Search</a></li>
								</ul>
							</li>
							<li role="presentation" class="active samples">
								<a href="/samples?query_id=@yield('sample_query_id', '')">
									1. Repertoire Metadata
								</a>
							</li>
							<li role="presentation" class="active sequences">
								<a href="#" class="active inactive">2. Sequences</a>
							</li>
						@else
							<li class="dropdown">
								<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">Search<span class="caret"></span></a>
								<ul class="dropdown-menu" role="menu">
								  <li><a href="/sequences-quick-search">Sequence Quick Search</a></li>
								  <li><a href="/samples">Repertoire Metadata Search</a></li>
								</ul>
							</li>
						@endif
					  @else
						  <li>
							<a class="login" href="/login">
								<span class="glyphicon glyphicon glyphicon-arrow-left" aria-hidden="true"></span>
								Home
							</a>
						  </li>
					  @endif
			      </ul>

			      <ul class="nav navbar-nav navbar-right">

					<li class="dropdown">
						<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">Help<span class="caret"></span></a>
						<ul class="dropdown-menu" role="menu">
							<li><a href="https://ireceptor.org/node/99" class="external" target="_blank"><span class="glyphicon glyphicon-question-sign" aria-hidden="true"></span> Frequently Asked Questions</a></li>
							<li><a href="http://ireceptor.org/platform/doc" class="external" target="_blank"><span class="glyphicon glyphicon-book" aria-hidden="true"></span> iReceptor Gateway documentation</a></li>
							<li role="separator" class="divider"></li>
							<li><a href="/fields-definitions"><span class="glyphicon glyphicon-list-alt" aria-hidden="true"></span> Data elements definitions</a></li>
						</ul>
					</li>


			    	@if(Auth::check())
				    	@if(Auth::user()->isAdmin())		    	
							<li class="dropdown">
								<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">admin<span class="caret"></span></a>
								<ul class="dropdown-menu" role="menu">
								  <li><a href="/admin/news"><span class="glyphicon glyphicon-comment" aria-hidden="true"></span> News</a></li>
								  <li><a href="/admin/databases"><span class="glyphicon glyphicon-globe" aria-hidden="true"></span> Repositories</a></li>
								  <li><a href="/admin/users"><span class="glyphicon glyphicon-user" aria-hidden="true"></span> Users</a></li>
								  <li role="separator" class="divider"></li>
								  <li><a href="/admin/queries"><span class="glyphicon glyphicon-eye-open" aria-hidden="true"></span> User Activity</a></li>
								  <li><a href="/cgi-bin/awstats.pl"><span class="glyphicon glyphicon-signal" aria-hidden="true"></span> Stats</a></li>
								  <li role="separator" class="divider"></li>
								  <li><a href="/admin/queues"><span class="glyphicon glyphicon-time" aria-hidden="true"></span> Queues</a></li>
								  <li role="separator" class="divider"></li>
								  <li><a href="/admin/field-names"><span class="glyphicon glyphicon-list-alt" aria-hidden="true"></span> Field names</a></li>
								  <li><a href="/canarie">CANARIE</a></li>
								</ul>
							</li>
						@endif
						<li class="dropdown">
							<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">{{ Auth::user()->username }}<span class="caret"></span></a>
							<ul class="dropdown-menu" role="menu">
								<li><a href="/user/account"><span class="glyphicon glyphicon-user" aria-hidden="true"></span> My account</a></li>
								  <li role="separator" class="divider"></li>
								<li><a href="/bookmarks"><span class="glyphicon glyphicon-star" aria-hidden="true"></span> Bookmarks</a></li>
								<li><a href="/downloads"><span class="glyphicon glyphicon-download-alt" aria-hidden="true"></span> Downloads</a></li>
								@if(config('services.agave.enabled'))
									<li><a href="/jobs"><span class="glyphicon glyphicon-time" aria-hidden="true"></span> Jobs</a></li>
									<li><a href="/systems"><span class="glyphicon glyphicon-cloud-upload" aria-hidden="true"></span> Systems</a></li>
								@endif
								  <li role="separator" class="divider"></li>
								<li><a href="/logout"><span class="glyphicon glyphicon-log-out" aria-hidden="true"></span> Log Out</a></li>
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
						@section('footer')
							<div class="mini_footer">
								<p class="text-right">
									<a href="http://ireceptor.org/" class="external" target="_blank">iReceptor public website</a> |
									<a href="/news">News</a> |
									<a href="/fields-definitions">Data elements definitions</a> |								
									<a href="http://ireceptor.org/platform/doc" class="external" target="_blank">Documentation</a>								
								</p>
							</div>
				        @show
					</div>
				</div>
			</div>
		</div>
		</footer>

		<!-- javascript -->
		<script src="https://code.jquery.com/jquery-1.12.4.min.js" integrity="sha256-ZosEbRLbNQzLpnKIkEdrPv7lOy9C27hHQ+Xp8a4MxAQ=" crossorigin="anonymous"></script>
		<script src="https://stackpath.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js" integrity="sha384-aJ21OjlMXNL5UyIl/XNwTMqvzeRMZH2w8c5cRVpzpU8Y5bApTppSuUkhZXN0VxHd" crossorigin="anonymous"></script>

		<script src="/js/jstree.min.js"></script>
		<script src="https://code.highcharts.com/highcharts.js"></script>
		<script src="https://code.highcharts.com/highcharts-3d.js"></script>
		<script src="https://code.highcharts.com/modules/data.js"></script>
		<script src="https://code.highcharts.com/modules/exporting.js"></script>
		<script src="https://code.highcharts.com/modules/drilldown.js"></script>
		<script src="https://code.highcharts.com/modules/bullet.js"></script>
		<script src="https://code.highcharts.com/modules/no-data-to-display.js"></script>  
		<script src="/js/bootstrap-multiselect.js"></script>
		<script src="/js/pluralize.js"></script>

		<script src="/js/airrvisualization.js?v=12"></script>
		<script src="/js/main.js?v=73"></script>
		<script src="/js/admin.js?v=1"></script>
		<script src="/js/visualization.js?v=20"></script>
	</body>

</html>