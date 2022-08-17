@extends('template')

@section('title', 'iReceptor Gateway Repositories')

@section('content')
<div class="container">

<!-- 	<div class="banner_title ">
		<h1><a href="https://www.antibodysociety.org/the-airr-community/airr-data-commons/">AIRR Data Commons</a> repositories queried by the iReceptor Gateway</h1>
	</div>
 -->
<!-- 	<div class="banner_title ">
		<h1>iReceptor Gateway Repositories</h1>
		<p class="sh1">List of the <a href="https://www.antibodysociety.org/the-airr-community/airr-data-commons/">AIRR Data Commons</a> repositories queried by the iReceptor Gateway.</p>
	</div>
 -->

	<!-- <h1><a href="https://www.antibodysociety.org/the-airr-community/airr-data-commons/">AIRR Data Commons</a> repositories queried by the iReceptor Gateway</h1> -->
	<h1>iReceptor Gateway repositories</h1>

	<p class="repositories_intro">
		Data shown on the iReceptor Gateway is retrieved in real time from these <a href="https://www.antibodysociety.org/the-airr-community/airr-data-commons/">AIRR Data Commons</a> repositories,<br />using the <a href="https://docs.airr-community.org/en/stable/api/adc_api.html">AIRR Data Commons API</a>. 
	</p>


<div class="row">

@for ($i = 1; $i <= 8; $i++)

	<div class="col-md-4 repository">

		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">VDJServer</h3>
				<h4>United States</h4>
				<img class="logo" src="/images/repositories/vdjserver.png" />
			</div>
			<div class="panel-body">
				<div class="row">
					<div class="col-md-7">
						<ul>
							<li>33 studies</li>
							<li>2949 repertoires</li>
							<li class="nb_sequences">1.4 billion sequences</li> 
						</ul>
					</div>
					<div class="col-md-5 browse-data">
						<a role="button" class="btn btn-primary"  href="">
							Browse data →
						</a>
					</div>				
				</div>
			</div>
<!-- 			<div class="panel-body">
				<h5>AIRR API</h5>
				<ul>
					<li>URL: https://vdjserver.org/airr/v1/info</li>
					<li>Version: 1.0</li>
				</ul>
			</div> -->

			<div class="panel-footer">
				<ul>
					<li>Website: <a class="external" target="_blank" href="https://vdjserver.org/airr/v1/info">https://vdjserver.org/airr/v1/info</a></li>
					<li>Contact: <a class="email" href="mailto:vdjserver@utsouthwestern.edu">vdjserver@utsouthwestern.edu</a></li>
					</ul>
			</div>
		</div>
<!-- 	 <p>
		 	Online platform for analyzing and sharing immune repertoire sequence data. 
		 </p>
 -->
	</div>
	@endfor

</div>


	<table class="table table-bordered table-striped rs_list">
				<thead>
					<tr>
						<th>Name</th>
						<th>Website</th>
						<th>Email</th>
						<th>Studies</th>
						<th>Repertoires</th>
						<th>Sequences</th>
						<th>Clones</th>
						<th>Cells</th>
						<th>API</th>
					</tr>
				</thead>
				<tbody>
					@foreach ($rs_list as $rs)
						<tr>		
							<td>{{ $rs->display_name }}</td>	
							<td><a href='{{ $rs->contact_url }}'>{{ $rs->contact_url }}</a></td>	
							<td><a href="mailto:{{ $rs->contact_email }}">{{ $rs->contact_email }}</a></td>	
							<td>{{ $rs->nb_studies }}</a></td>
							<td>{{ $rs->nb_samples }}</a></td>
							<td>
								<span title="{{ number_format($rs->nb_sequences) }}">
									{{ human_number($rs->nb_sequences) }}
								</span>
							</td>
							<td>
								<span title="{{ number_format($rs->nb_clones) }}">
									{{ human_number($rs->nb_clones) }}
								</span>
							</td>
							<td>
								<span title="{{ number_format($rs->nb_cells) }}">
									{{ human_number($rs->nb_cells) }}
								</span>
							</td>
							<td>{{ $rs->api_version }}</td>
						</tr>
					@endforeach
				</tbody>
			</table>
</div>
@stop

