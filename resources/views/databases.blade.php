@extends('template')

@section('title', 'Repositories')

@section('content')
<div class="container">
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
						<th>Enabled</th>
						<th>Name</th>
						<th>Visible Name</th>
						<th>URL</th>
						<th>Nb repertoires</th>
						<th>Nb sequences</th>
						<th>Last refreshed</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					@foreach ($rs_list as $rs)
						<tr>
							<td>{{ Form::checkbox('rs_enabled', $rs->id, $rs->enabled) }}</td>					
							<td>{{ $rs->name }}</td>	
							<td>{{ $rs->display_name }}</td>	
							<td><a href="{{ $rs->url }}">{{ $rs->url }}</a></td>					
							<td>{{ $rs->nb_samples }}</a></td>
							<td>
								<span title="{{ number_format($rs->nb_sequences) }}">
									{{ human_number($rs->nb_sequences) }}
								</span>
							</td>
							<td class="text-nowrap">
								@if ($rs->last_cached)
									<span class="minor">{{ human_date_time($rs->last_cached, 'D') }}</span>
									{{ human_date_time($rs->last_cached, 'M j') }}
									<span class="minor">{{ human_date_time($rs->last_cached, 'H:i') }}</span>
								@endif
							</td>
							<td>
								<a href="/admin/samples/update-sequence_count/{{ $rs->id }}">
									<button type="button" class="btn btn-default" aria-label="Edit">
										<span class="glyphicon glyphicon-repeat" aria-hidden="true"></span>
										Refresh cached sequence counts
									</button>
								</a>
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

		</div>
	</div>
</div>
@stop

