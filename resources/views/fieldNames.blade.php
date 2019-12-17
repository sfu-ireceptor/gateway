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
							<th>ir_short</th>
							<th>ir_full</th>
							<th>ir_class</th>
							<th>ir_subclass</th>
							<th>airr</th>
							<th>airr_full</th>
							<th>airr_description</th>
							<th>airr_example</th>
							<th>ir_adc_api_query</th>
							<th>ir_adc_api_response</th>
							<th>airr_type</th>
							<th>ir_api_input_type</th>
						</tr>
					</thead>
					<tbody>
						@foreach ($field_name_list as $s)
						<tr>
							<td class="text-nowrap">{{ $s['ir_id'] }}</td>
							<td class="text-nowrap">{{ $s['ir_short'] }}</td>
							<td class="text-nowrap">{{ $s['ir_full'] }}</td>
							<td class="text-nowrap">{{ $s['ir_class'] }}</td>
							<td class="text-nowrap">{{ $s['ir_subclass'] }}</td>
							<td class="text-nowrap">{{ $s['airr'] }}</td>
							<td class="text-nowrap">{{ $s['airr_full'] }}</td>
							<td class="text-nowrap">{{ $s['airr_description'] }}</td>
							<td class="text-nowrap">{{ $s['airr_example'] }}</td>
							<td class="text-nowrap">{{ $s['ir_adc_api_query'] }}</td>
							<td class="text-nowrap">{{ $s['ir_adc_api_response'] }}</td>
							<td class="text-nowrap">{{ $s['airr_type'] }}</td>
							<td class="text-nowrap">{{ $s['ir_api_input_type'] }}</td>
						@endforeach
					</tbody>
				</table>
			@endif
		</div>
	</div>
</div>
@stop

