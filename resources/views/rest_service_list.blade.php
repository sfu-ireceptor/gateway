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
						     	<span class="node_name">
						     		@isset($rs_data['rs_name'])
							     		{{ $rs_data['rs_name'] }}
						     		@endisset
						     		
						     		@isset($rs_data['rs']->display_name)
						     			{{ $rs_data['rs']->display_name }}
						     		@endisset
						     	</span>
						     	<em>{{ human_number($rs_data['total_sequences']) }} sequences</em>
							    <ul>
						 			@foreach ($rs_data['study_tree'] as $lab)
										<li class="lab_node" data-jstree='{"opened":true, "disabled":true, "icon":"glyphicon glyphicon-education"}'>
											<span title="{{ $lab['name'] }}" class="lab_name">
												Lab:
												@if(isset($lab['name']) && $lab['name'] != '')
													{{ str_limit($lab['name'], $limit = 64, $end = '‥') }}
												@else
													<em>unknown</em>
												@endif
											</span>
											@if(isset($lab['total_sequences']) && $lab['total_sequences'] > 0)
												<em>{{ human_number($lab['total_sequences']) }} sequences</em>
											@endif
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
																@if ($study['total_sequences'] > 0)
																	<em>{{ human_number($study['total_sequences']) }} sequences</em>
																@endif
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
</div>