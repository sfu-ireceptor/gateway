@extends('template')

@section('title', 'Data elements definitions')

@section('content')
<div class="container">
	<h1>Data elements definitions</h1>

	<p>The terms describing the data stored in the network of AIRR-seq repositories federated by the iReceptor Gateway are driven by the recommendations of the AIRR Community: the <a href="https://www.nature.com/articles/ni.3873">AIRR Minimal Standards (MiAIRR)</a> for study metadata and the <a href="https://www.frontiersin.org/articles/10.3389/fimmu.2018.02206/full">AIRR Rearrangement schema</a> representing annotated rearrangements. For more information, visit the <a href="http://docs.airr-community.org/">AIRR Community documentation page</a>.</p>
	<p>The definitions and relevant examples for each field are given below:</p>

	<!-- tabs -->
	<ul class="nav nav-tabs" role="tablist">
	<li role="presentation" class="active"><a href="#metadata" role="tab" data-toggle="tab">Metadata</a></li>
	<li role="presentation"><a href="#sequences" role="tab" data-toggle="tab">Sequences</a></li>
	</ul>

	<!-- tab panes -->
	<div class="tab-content fields_definitions">

		<div role="tabpanel" class="tab-pane active" id="metadata">
			<div class="row">
				<div class="col-md-12">
					@foreach ($sample_field_list_grouped as $field_group)
						<h2>{{ $field_group['name'] }}</h2>
						<table class="table table-striped">
							<thead>
								<tr>
									<th></th>
									<th></th>
									<th>Example</th>
								</tr>
							</thead>
							<tbody>
								@foreach ($field_group['fields'] as $s)
								<tr>
									<td class="text-nowrap"><strong>{{ $s['ir_short'] }}</strong></td>
									<td class="">{{ $s['airr_description'] }}</td>
									<td class="">{{ $s['airr_example'] }}</td>
								@endforeach
							</tbody>
						</table>
					@endforeach
				</div>
			</div>
		</div>

		<div role="tabpanel" class="tab-pane" id="sequences">
			<div class="row">
				<div class="col-md-12">
					@foreach ($sequence_field_list_grouped as $field_group)
						<h2>{{ $field_group['name'] }}</h2>
						<table class="table table-striped">
							<thead>
								<tr>
									<th></th>
									<th></th>
									<th>Example</th>
								</tr>
							</thead>
							<tbody>
								@foreach ($field_group['fields'] as $s)
								<tr>
									<td class="text-nowrap"><strong>{{ $s['ir_short'] }}</strong></td>
									<td class="">{{ $s['airr_description'] }}</td>
									<td class="">{{ $s['airr_example'] }}</td>
								@endforeach
							</tbody>
						</table>
					@endforeach
				</div>
			</div>
		</div>

	</div>

</div>
@stop

