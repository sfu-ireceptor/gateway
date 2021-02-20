@extends('template')

@section('title', 'Repository Stats')

@section('content')
<div class="container">
	<h1>{{ $rs['name'] }} - Stats Testing<small>Checks if the AIRR visualization library throws an exception</small></h1>

	<div class="row">
		<div class="col-md-12">

			<table class="table table-bordered table-striped table-condensed rs_stats">
				<thead>
					<tr>
						<th>Study</th>
						<th>Repertoire</th>
						<th>ID</th>
						<th>V-Gene</th>
						<th>D-Gene</th>
						<th>J-Gene</th>
						<th>Junc. Len.</th>
						<th></th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					@foreach ($sample_list as $sample)
						@if(isset($sample->stats) && $sample->stats)
						<tr data-url="/samples/stats/{{ $sample->real_rest_service_id }}/{{ $sample->repertoire_id }}">
							<td>
								{{ str_limit($sample->study_title, $limit = 50, $end = '‥') }}
							</td>
							<td>
								{{ $sample->sample_id }}
							</td>
							<td>
								{{ $sample->repertoire_id }}
							</td>
							<td class="v_gene_usage">
								<span class="label label-default">Pending</span>
							</td>
							<td class="d_gene_usage">													
								<span class="label label-default">Pending</span>
							</td>
							<td class="j_gene_usage">													
								<span class="label label-default">Pending</span>
							</td>
							<td class="junction_length_stats">													
								<span class="label label-default">Pending</span>
							</td>
							<td>
								@if(isset($sample->stats) && $sample->stats)
									<a href="#modal_stats" data-url="/samples/stats/{{ $sample->real_rest_service_id }}/{{ $sample->repertoire_id }}" data-repertoire-name="{{ $sample->subject_id }} - {{ $sample->sample_id }} - {{ $sample->pcr_target_locus }}" data-toggle="modal" data-target="#statsModal" title="Repertoire statistics">
										<span class="label label-primary">
											<span class="glyphicon glyphicon-stats" aria-hidden="true"></span>
										</span>
									</a>
								@endif								
							</td>
							<td class="">
								<button type="button" class="btn btn-xs btn-default" data-url="/samples/stats/{{ $sample->real_rest_service_id }}/{{ $sample->repertoire_id }}" data-toggle="modal" data-target="#statsModalJSON" >JSON</button>
							</td>
						</tr>
						@endif	
					@endforeach
				</tbody>
			</table>

			<!-- Repertoire Statistics JSON Modal -->
			<div class="modal fade" id="statsModalJSON" tabindex="-1" role="dialog">
				<div class="modal-dialog" role="document">
					<div class="modal-content">
						<div class="modal-body" id="">
							<!-- Nav tabs -->
							<ul class="nav nav-tabs" role="tablist">
								<li role="presentation" class="active"><a href="#stats_vgene" data-stat="v_gene_usage" role="tab" data-toggle="tab">V-gene</a></li>
								<li role="presentation"><a href="#stats_dgene" data-stat="d_gene_usage"role="tab" data-toggle="tab">D-gene</a></li>
								<li role="presentation"><a href="#stats_jgene" data-stat="j_gene_usage" role="tab" data-toggle="tab">J-gene</a></li>
								<li role="presentation"><a href="#stats_junction_length" data-stat="junction_length_stats" role="tab" data-toggle="tab">Junction Length</a></li>
							</ul>

							<!-- Tab panes -->
							<div class="tab-content">
								<div role="tabpanel" class="tab-pane active" id="stats_vgene"><pre>Loading V-gene JSON...</pre></div>
								<div role="tabpanel" class="tab-pane" id="stats_dgene"><p>Loading D-gene JSON...</p></div>
								<div role="tabpanel" class="tab-pane" id="stats_jgene"><p>Loading J-gene JSON...</p></div>
								<div role="tabpanel" class="tab-pane" id="stats_junction_length"><p>Loading Junction Length JSON...</p></div>
							</div>
						</div>

						<div class="modal-footer">
							<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
						</div>
					</div>
				</div>
			</div>

			<!-- Repertoire Statistics Modal -->
			<div class="modal fade" id="statsModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
				<div class="modal-dialog" role="document">
					<div class="modal-content">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
							<h4 class="modal-title" id="statsModalLabel">Modal title</h4>
						</div>
						<div class="modal-body" id="">
							<!-- Nav tabs -->
							<ul class="nav nav-tabs" role="tablist">
								<li role="presentation" class="active"><a href="#stats_vgene" data-stat="v_gene_usage" role="tab" data-toggle="tab">V-gene</a></li>
								<li role="presentation"><a href="#stats_dgene" data-stat="d_gene_usage"role="tab" data-toggle="tab">D-gene</a></li>
								<li role="presentation"><a href="#stats_jgene" data-stat="j_gene_usage" role="tab" data-toggle="tab">J-gene</a></li>
								<li role="presentation"><a href="#stats_junction_length" data-stat="junction_length_stats" role="tab" data-toggle="tab">Junction Length</a></li>
							</ul>

							<!-- Tab panes -->
							<div class="tab-content">
								<div role="tabpanel" class="tab-pane active" id="stats_vgene"><p>Loading V-gene graph...</p></div>
								<div role="tabpanel" class="tab-pane" id="stats_dgene"><p>Loading D-gene graph...</p></div>
								<div role="tabpanel" class="tab-pane" id="stats_jgene"><p>Loading J-gene graph...</p></div>
								<div role="tabpanel" class="tab-pane" id="stats_junction_length"><p>Loading Junction Length graph...</p></div>
							</div>
						</div>

						<div class="modal-footer sample_stats_info">
							<p class="loading">Loading repertoire metadata...</p>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
						</div>
					</div>
				</div>
			</div>

		</div>
	</div>
</div>
@stop

