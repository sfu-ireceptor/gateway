@extends('template')

@section('title', 'Databases')

@section('content')
<div class="container">
	<h1>Databases</h1>
	<div class="row">
		<div class="col-md-4">

			<table class="table table-bordered table-striped rs_list">
				<thead>
					<tr>
						<th>Enabled</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					@foreach ($rs_list as $rs)
						<tr>
							<td>{{ Form::checkbox('rs_enabled', $rs->id, $rs->enabled) }}</td>					
							<td>{{ $rs->name }}</td>					
						</tr>
					@endforeach
				</tbody>
			</table>

		</div>
	</div>
</div>
@stop

