@extends('template')

@section('title', 'Home')

@section('content')
<div class="container home_container">

	<div class="row">
		<div class="col-md-12">
			<p class="intro">
				iReceptor provides access to
				{{ number_format($total_sequences) }} sequences from
				{{ $total_repositories }} remote repositories,
				{{ $total_labs }} research labs,
				{{ $total_projects }} studies, and
				{{ $total_samples }} samples.
				<button type="button" class="btn btn-default btn-xs" data-toggle="modal" data-target="#myModal">
					Details
				</button>
			</p>

		</div>
	</div>
	<!-- Modal -->
	<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
  		<div class="modal-dialog" role="document">
	    	<div class="modal-content">
	      		<div class="modal-header">
	        		<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			        <h4 class="modal-title" id="myModalLabel">
			        	{{ $total_repositories }} remote {{ str_plural('repository', $total_repositories)}},
			        	{{ $total_labs }} research {{ str_plural('lab', $total_labs)}},
			        	{{ $total_projects }} {{ str_plural('study', $total_projects)}}
			        </h4>
	      		</div>
		  		<div class="modal-body">
		        	<div id="rest_service_list">
						<ul>
							@foreach ($rest_service_list as $rs_data)
							    <li  class="rs_node" data-jstree='{"opened":true, "disabled":true, "icon":"glyphicon glyphicon-home"}'>
							     	<span class="node_name">{{ $rs_data['rs']->name }}</span>
							     	<em>{{ human_number($rs_data['total_sequences']) }} sequences</em>
								    <ul>
							 			@foreach ($rs_data['study_tree'] as $lab)
											<li class="lab_node" data-jstree='{"opened":true, "disabled":true, "icon":"glyphicon glyphicon-education"}'>
												<span title="{{ $lab['name'] }}" class="lab_name">
													Lab:
													@isset($lab['name'])
													{{ str_limit($lab['name'], $limit = 64, $end = '‥') }}
													@endisset
												</span>
												@isset($lab['total_sequences'])
													<em>{{ human_number($lab['total_sequences']) }} sequences</em>
												@endisset
											    <ul>
											    	@isset($lab['studies'])
									 					@foreach ($lab['studies'] as $study)
									 						<li data-jstree='{"icon":"glyphicon glyphicon-book", "disabled":true}'>
									 							<span>
																	Study:
																	@if (isset($study['study_url']))
																		<a href="{{ $study['study_url'] }}" title="{{ $study['study_title'] }}" target="_blank">
																			{{ str_limit($study['study_title'], $limit = 64, $end = '‥') }} (NCBI)
																		</a>
																	@else
																		<span title="{{ $study['study_title'] }}">
																			{{ str_limit($study['study_title'], $limit = 64, $end = '‥') }}
																		</span>
																	@endif
																	 <em>{{ human_number($study['total_sequences']) }} sequences</em>
																</span>
															</li>
														@endforeach
													@endisset
										 		</ul>
											</li>
								 		@endforeach
							 		</ul>
							    </li>
							@endforeach
						</ul>
					</div>
		      	</div>
		      	<div class="modal-footer">
		        	<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
		      	</div>
		    </div>
	  	</div>
	</div> <!-- Modal -->

	<div class="row">
		<div class="col-md-9">
			<div id="landing_charts">
				<div class="row">
					<div class="col-md-4 chart" id="landing_chart1"></div>
					<div class="col-md-4 chart" id="landing_chart2"></div>
					<div class="col-md-4 chart" id="landing_chart3"></div>
				</div>
			</div>
		</div>
		<div class="col-md-3 side_search_links">
			<p class="quick_search_link">
				<a class="btn btn-primary btn-lg" role="button" href="/sequences-quick-search">Sequence Search →</a>
			</p>
			<p>Find interesting sequences and sequence annotations by searching for sequence features (Junction, V/D/J Gene)</p>
		</div>
	</div>
	<div class="row">
		<div class="col-md-9">
			<div id="landing_charts">	
				<div class="row">
					<div class="col-md-4 chart" id="landing_chart4"></div>
					<div class="col-md-4 chart" id="landing_chart5"></div>
					<div class="col-md-4 chart" id="landing_chart6"></div>
				</div>
			</div>
		</div>
		<div class="col-md-3 side_search_links">
			<p class="adv_search_link">	
				<a  class="btn btn-primary btn-lg" role="button" href="/samples">Metadata Search →</a>
			</p>
			<p>Find interesting sequences and sequence annotations by exploring study, subject, and sample metadata</p>
		</div>
	</div>
	<div class="login_fold_box">
	</div>

	<div class="row">
		<div class="login_about_box">
		<div class="col-md-9">
			<h4>
			About iReceptor
			</h4>
			<p>
			iReceptor is a distributed data management system and scientific gateway for mining “Next Generation”sequence data from immune responses. The goal of the project is to: improve the design of vaccines, therapeutic antibodies and cancer immunotherapies by integrating Canadian and international data repositories of antibody and T-cell receptor gene sequences.
			</p>
			<p>
			iReceptor provides a technology platform that will lower the barrier to immune genetics researchers who need to federate large, distributed, immune genetics data repositories in order to answer complex questions about the immune response. The focus of the iReceptor project is to leverage existing capabilities and technologies to build a new scientific platform for the immune genetics research community.
			</p>
		</div>
		</div>
		<div class="col-md-3">
		<div class="login_about_box">
			<h4>
			Contact/Resources
			</h4>
			<p>
				To ask question or get involved, email: support@ireceptor.org.
			</p>
			<p>
				To learn more about iReceptor visit the <a href="http://www.ireceptor.org" target="_blank">iReceptor website</a>.
			</p>
			<p>
				To learn more about the Adaptive Immune Response Repertoire (AIRR) Community, visit the
				<a href="http://www.airr-community.org" target="_blank">AIRR website</a>.
			</p>
		</div>
		</div>
	</div>
	<div class="login_fold_box">
	</div>

	<div class="row">
		<div class="col-md-4">
			<h4>Funded by</h4>
			<a href="http://www.innovation.ca" class="cfi">
				<img src="/images/logos/cfi.png"><br />
			</a>
			<a href="http://www2.gov.bc.ca/gov/content/governments/about-the-bc-government/technology-innovation/bckdf" class="bckdf">
				<img src="/images/logos/bckdf.png"><br />
			</a>
			<a href="http://www.canarie.ca" class="canarie">
				<img src="/images/logos/canarie.png">
			</a>
		</div>
		<div class="col-md-4">
			<h4>Developed and Run by</h4>
			<a href="http://www.irmacs.sfu.ca/" class="irmacs">
				<img src="/images/logos/irmacs.png">
			</a>
		</div>
		<div class="col-md-4">
			<h4 class="powered">Powered by</h4>
			<a href="https://www.computecanada.ca/" class="compute_canada">
				<img src="/images/logos/compute_canada.png">
			</a>
			<a href="http://agaveapi.co/" class="agave">
				<img src="/images/logos/agave.png">
			</a>
		</div>

	</div>

</div>

<script>
	var graphFields = [
	        "@lang('v2.study_description')", 
	        "@lang('v2.organism')",
	        "@lang('v2.disease_state_sample')", 
	        "@lang('v2.tissue')",
	        "@lang('v2.cell_subset')", 
	        "@lang('v2.template_class')"
	    ];
	var graphNames = [
	        "@lang('short.study_description')",
	        "@lang('short.organism')", 
	        "@lang('short.disease_state_sample')",
	        "@lang('short.tissue')", 
	        "@lang('short.cell_subset')", 
	        "@lang('short.template_class')"
	    ];
var graphDIV = "landing_chart";
var graphInternalLabels = false;
var graphLabelLength = 10;
var graphCountField = "ir_sequence_count";
var graphData = {!! $sample_list_json !!};
</script>

@stop

	