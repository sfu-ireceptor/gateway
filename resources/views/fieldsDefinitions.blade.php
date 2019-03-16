@extends('template')

@section('title', 'Data elements definitions')

@section('content')
<div class="container">
	<h1>Data elements definitions</h1>
	<h3>Metadata</h3>
	<div class="row">
		<div class="col-md-12">
			@if (count($sample_field_list) > 0)
				<table class="table table-striped system_list">
					<thead>
						<tr>
							<th>Name</th>
							<th>Description</th>
							<th>Example</th>
						</tr>
					</thead>
					<tbody>
						@foreach ($sample_field_list as $s)
						<tr>
							<td class="text-nowrap"><strong>{{ $s['ir_short'] }}</strong></td>
							<td class="">{{ $s['airr_description'] }}</td>
							<td class="">{{ $s['airr_example'] }}</td>
						@endforeach
					</tbody>
				</table>
			@endif
		</div>
	</div>

	<h3>Sequences</h3>
		<div class="row">
			<div class="col-md-12">
				@if (count($sequence_field_list) > 0)
					<table class="table table-striped system_list">
						<thead>
							<tr>
								<th>Name</th>
								<th>Description</th>
								<th>Example</th>
							</tr>
						</thead>
						<tbody>
							@foreach ($sequence_field_list as $s)
							<tr>
								<td class="text-nowrap"><strong>{{ $s['ir_short'] }}</strong></td>
								<td class="">{{ $s['airr_description'] }}</td>
								<td class="">{{ $s['airr_example'] }}</td>
							@endforeach
						</tbody>
					</table>
				@endif
			</div>
		</div></div>
@stop

