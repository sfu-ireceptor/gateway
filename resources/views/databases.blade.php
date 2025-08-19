@extends('template')

@section('title', 'Repositories')

@section('content')
<div class="container-fluid">
	<h1>Repositories <small>Choose those available to <strong>all</strong> users of <strong>this</strong> gateway</small></h1>

	@if (isset($notification))
	<div class="alert alert-warning alert-dismissible" role="alert">
		<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		{!! $notification !!}
	</div>
	@endif

	<div class="row">
		<div class="col-md-12">

			<table class="table table-bordered table-striped rs_list">
				<thead>
					<tr>
						<th>Private Name</th>
						<th>Public Group Name</th>
						<th>URL</th>
						<th>Repertoires</th>
						<th>Sequences</th>
						<th>Clones</th>
						<th>Cells</th>
						<th>Stats</th>
						<th>max_size</th>
						<th>API Version</th>
						<th>Last Refresh</th>
					</tr>
				</thead>
				<tbody>
					@foreach ($rs_list as $rs)
						<tr>
							<td>
								<div class="btn-group">
									<button type="button" class="btn btn-sm {{ $rs->enabled ? 'btn-success' : 'btn-default'}} dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
										{{ $rs->name }}
										<span class="caret"></span>
									</button>  
									<ul class="dropdown-menu">
										@if ($rs->enabled)
										    <li>
										    	<a href="/admin/update-database/{{ $rs->id }}/0">
										    		<span class="glyphicon glyphicon-remove" aria-hidden="true"></span>
										    		Disable
										    	</a>
										    </li>
										@else
										    <li>
										    	<a href="/admin/update-database/{{ $rs->id }}/1">
										    		<span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
										    		Enable
										    	</a>
										    </li>
										@endif
										<li role="separator" class="divider"></li>
										<li>
											<a href="/admin/samples/update-sequence_count/{{ $rs->id }}">
												<span class="glyphicon glyphicon-repeat" aria-hidden="true"></span>
												Refresh cached <strong>sequence</strong> counts
											</a>
										</li>
										<li>
											<a href="/admin/samples/update-clone_count/{{ $rs->id }}">
												<span class="glyphicon glyphicon-repeat" aria-hidden="true"></span>
												Refresh cached <strong>clone</strong> counts
											</a>
										</li>
										<li>
											<a href="/admin/samples/update-cell_count/{{ $rs->id }}">
												<span class="glyphicon glyphicon-repeat" aria-hidden="true"></span>
												Refresh cached <strong>cell</strong> counts
											</a>
										</li>
										<li>
											<a href="/admin/samples/update-antigens/{{ $rs->id }}">
												<span class="glyphicon glyphicon-repeat" aria-hidden="true"></span>
												Refresh cached <strong>antigens</strong>
											</a>
										</li>
										<li>
											<a href="/admin/samples/update-species/{{ $rs->id }}">
												<span class="glyphicon glyphicon-repeat" aria-hidden="true"></span>
												Refresh cached <strong>species</strong>
											</a>
										</li>
										<li>
											<a href="/admin/samples/update-epitopes/{{ $rs->id }}">
												<span class="glyphicon glyphicon-repeat" aria-hidden="true"></span>
												Refresh cached <strong>epitopes</strong>
											</a>
										</li>
										<li role="separator" class="divider"></li>
										<li>
											<a href="/admin/update-chunk-size/{{ $rs->id }}">
												<span class="glyphicon glyphicon-repeat" aria-hidden="true"></span>
												Refresh <code>/info</code> data
											</a>
										</li>
										<li role="separator" class="divider"></li>
										<li>
											<a href="/admin/database-stats/{{ $rs->id }}" class="text-nowrap external" target="_blank">
												<span class="glyphicon glyphicon-check" aria-hidden="true"></span>
												Test stats
											</a>
										</li>
									</ul>
								</div>
							</td>					
							<td>{{ $rs->display_name }}</td>	
							<td><a href="{{ $rs->url }}">{{ $rs->url }}</a></td>					
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
							<td>
								@if($rs->stats)
									<a href="/admin/database-stats/{{ $rs->id }}" class="text-nowrap external" target="_blank" title="Test Stats">Yes</a>
								@endif
							</td>
							<td>{{ $rs->chunk_size }}</td>
							<td>{{ $rs->api_version }}</td>
							<td class="text-nowrap">
								@if ($rs->last_cached)
									<span class="minor">{{ human_date_time($rs->last_cached, 'D') }}</span>
									{{ human_date_time($rs->last_cached, 'M j') }}
									<span class="minor">{{ human_date_time($rs->last_cached, 'H:i') }}</span>
								@endif
							</td>
						</tr>
					@endforeach
				</tbody>
			</table>

			<p>
				<a href="/admin/samples/update-cache">
					<button type="button" class="btn btn-default" aria-label="Edit">
						<span class="glyphicon glyphicon-repeat" aria-hidden="true"></span>
						Refresh cached repertoire metadata
					</button>
				</a>
			</p>
			<p>Used by login/home pages (for the numbers and charts) and to populate search forms widgets.</p>
		</div>
	</div>
</div>
@stop

