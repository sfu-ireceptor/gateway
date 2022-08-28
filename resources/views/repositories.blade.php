@extends('template')

@section('title', 'iReceptor Gateway Repositories')

@section('content')
<div class="container">

	<h1>iReceptor Gateway repositories</h1>

	<p class="repositories_intro">
		Data displayed on the iReceptor Gateway is retrieved in real time from these <a href="https://www.antibodysociety.org/the-airr-community/airr-data-commons/">AIRR Data Commons</a> repositories,<br />using the <a href="https://docs.airr-community.org/en/stable/api/adc_api.html">AIRR Data Commons API</a>. 
	</p>

	<div class="row">
		@foreach ($rs_list as $rs)
			<div class="col-md-4 repository">
				<div class="panel panel-default">
					
					<div class="panel-heading">
						<h3 class="panel-title">{{ $rs->display_name }}</h3>
						<h4>{{ $rs->country }}</h4>
						@if ($rs->logo != '')
							<a class="logo" href="{{ $rs->contact_url }}" target="_blank" >
								<img src="/images/repositories/{{ $rs->logo }}" />
							</a>							
						@endif
					</div>
					
					<div class="panel-body">
						<div class="row">
							<div class="col-md-7">
								<ul>
									<li>{{ $rs->nb_studies }} {{ str_plural('study', $rs->nb_studies)}}</li>
									<li>{{ $rs->nb_samples }} {{ str_plural('repertoire', $rs->nb_samples)}}</li>
									<li>{{ human_number($rs->nb_sequences) }} {{ str_plural('sequence', $rs->nb_sequences)}}</li>
									@if($rs->nb_clones > 0)
										<li>{{ human_number($rs->nb_clones) }} {{ str_plural('clone', $rs->nb_clones)}}</li>
									@endif
									@if($rs->nb_cells > 0)
										<li>{{ human_number($rs->nb_cells) }} {{ str_plural('cell', $rs->nb_cells)}}</li>
									@endif
								</ul>
							</div>

							@if(Auth::check())
								<div class="col-md-5 browse-data">
									<a role="button" class="btn btn-primary"  href="/samples?rest_service_name={{ $rs->display_name }}">
										Browse data →
									</a>
								</div>
							@endif

						</div>
					</div>

					<div class="panel-footer">
						<ul>
							<li>
								<span class="glyphicon glyphicon-globe" aria-hidden="true"></span> 
								<a class="external" target="_blank" href="{{ $rs->contact_url }}">{{ $rs->contact_url }}</a>
							</li>
							<li>
								<span class="glyphicon glyphicon-envelope" aria-hidden="true"></span> 
								<a class="email" href="mailto:{{ $rs->contact_email }}">{{ $rs->contact_email }}</a>
							</li>
							</ul>
					</div>

				</div>
			</div>
			@if( ! $loop->last && ($loop->iteration % 3 == 0))
				</div>
				<div class="row">
			@endif
		@endforeach
	</div>

</div>
@stop

