@extends('template')

@section('title', 'Field names')

@section('content')
<div class="container-fluid">
	<h1>Field names</h1>
	<div class="row">
		<div class="col-md-12">
			@if (count($field_name_list) > 0)
				<table class="table table-striped system_list">
					<thead>
						<tr>
							<th>ir_id</th>
							<th>ir_v2</th>
							<th>ir_short</th>
							<th>ir_v1</th>
							<th>ir_v1_sql</th>
							<th>airr</th>
							<th>ir_full</th>
							<th>airr_full</th>
						</tr>
					</thead>
					<tbody>
						@foreach ($field_name_list as $s)
						<tr>
							<td class="text-nowrap">{{ $s['ir_id'] }}</td>
							<td class="text-nowrap">{{ $s['ir_v2'] }}</td>
							<td class="text-nowrap">{{ $s['ir_short'] }}</td>
							<td class="text-nowrap">{{ $s['ir_v1'] }}</td>
							<td class="text-nowrap">{{ $s['ir_v1_sql'] }}</td>
							<td class="text-nowrap">{{ $s['airr'] }}</td>
							<td class="text-nowrap">{{ $s['ir_full'] }}</td>
							<td class="text-nowrap">{{ $s['airr_full'] }}</td>
						@endforeach
					</tbody>
				</table>
			@endif
		</div>
	</div>
</div>
@stop

