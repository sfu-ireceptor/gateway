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
	<h1>AIRR Data Commons repositories queried by the iReceptor Gateway</h1>

<!-- 	<p>
		List of the <a href="https://www.antibodysociety.org/the-airr-community/airr-data-commons/">AIRR Data Commons</a> repositories which can be browsed through the iReceptor Gateway.
	</p>
 -->

	<table class="table table-bordered table-striped rs_list">
				<thead>
					<tr>
						<th>Name</th>
						<th>Website</th>
						<th>Email</th>
						<th>Repertoires</th>
						<th>Sequences</th>
						<th>Clones</th>
						<th>Cells</th>
						<th>AIRR API Version</th>
					</tr>
				</thead>
				<tbody>
					@foreach ($rs_list as $rs)
						<tr>		
							<td>{{ $rs->display_name }}</td>	
							<td><a href='{{ $rs->contact_url }}'>{{ $rs->contact_url }}</a></td>	
							<td><a href="mailto:{{ $rs->contact_email }}">{{ $rs->contact_email }}</a></td>	
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

