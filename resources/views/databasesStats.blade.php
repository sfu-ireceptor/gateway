@extends('template')

@section('title', 'Repositories Stats')

@section('content')
<div class="container">
	<h1>Repositories Stats <small>Checks JSON stats using the AIRR visualization library</small></h1>

	<div class="row">
		<div class="col-md-12">

			<table class="table table-bordered table-striped table-condensed rs_stats">
				<thead>
					<tr>
						<th>Repository</th>
						<th>Study</th>
						<th>Repertoire ID</th>
						<th>Repertoire Name</th>
						<th>V-Gene</th>
						<th>D-Gene</th>
						<th>J-Gene</th>
						<th>Junction Length</th>
					</tr>
				</thead>
				<tbody>
					@foreach ($sample_list as $sample)
						@if(isset($sample->stats) && $sample->stats)
						<tr data-url="/samples/stats/{{ $sample->real_rest_service_id }}/{{ $sample->repertoire_id }}">
							<td>
								{{ $sample->rest_service_name }}
							</td>
							<td>
								{{ str_limit($sample->study_title, $limit = 30, $end = '‥') }}
							</td>
							<td>
								{{ $sample->repertoire_id }}
							</td>
							<td>
								{{ $sample->subject_id }} - {{ $sample->sample_id }} - {{ $sample->pcr_target_locus }}
							</td>
							<td class="v_gene_usage">													
								<button type="button" class="btn btn-xs btn-default">Pending</button>
							</td>
							<td class="d_gene_usage">													
								<button type="button" class="btn btn-xs btn-default">Pending</button>
							</td>
							<td class="j_gene_usage">													
								<button type="button" class="btn btn-xs btn-default">Pending</button>
							</td>
							<td class="junction_length_stats">													
								<button type="button" class="btn btn-xs btn-default">Pending</button>
							</td>
						</tr>
						@endif	
					@endforeach
				</tbody>
			</table>


		</div>
	</div>
</div>
@stop

