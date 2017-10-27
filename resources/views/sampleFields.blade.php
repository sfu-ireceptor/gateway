@extends('template')

@section('title', 'Sample fields')

@section('content')
<div class="container">
	<h1>Sample fields</h1>
	<div class="row">
		<div class="col-md-12">
			@if (count($sample_field_list) > 0)
				<table class="table table-striped system_list">
					<thead>
						<tr>
							<th>ir_id</th>
							<th>ir_v2</th>
							<th>ir_short</th>
							<th>airr</th>
							<th>ir_full</th>
							<th>airr_full</th>
						</tr>
					</thead>
					<tbody>
						@foreach ($sample_field_list as $s)
						<tr>
							<td class="text-nowrap">{{ $s['ir_id'] }}</td>
							<td class="text-nowrap">{{ $s['ir_v2'] }}</td>
							<td class="text-nowrap">{{ $s['ir_short'] }}</td>
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

