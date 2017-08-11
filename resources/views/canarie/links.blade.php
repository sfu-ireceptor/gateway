@extends('template')

@section('title', 'CANARIE links')

@section('content')
<div class="container">
	<h1>CANARIE</h1>

	<div class="row">
		<div class="col-md-6">
			<h2>CANARIE availability pages</h2>
			<ul>
				<li><a href="https://science.canarie.ca/researchmiddleware/researchresource/stats.html?resourceID=73">Gateway</a></li>
				<li><a href="https://science.canarie.ca/researchmiddleware/researchresource/stats.html?resourceID=44">Authentication</a></li>
				<li><a href="https://science.canarie.ca/researchmiddleware/researchresource/stats.html?resourceID=43">Computation</a></li>
				<li><a href="https://science.canarie.ca/researchmiddleware/researchresource/stats.html?resourceID=41">Database</a></li>
				<li><a href="https://science.canarie.ca/researchmiddleware/researchresource/stats.html?resourceID=42">Database Migration</a></li>
			</ul>
		</div>
	</div>	

	<h2>CANARIE links on this gateway</h2>

	<div class="row">
		<div class="col-md-3">
			<h4>Gateway</h4>
			<ul>
				<li><a href="{{ URL::to('/platform/info') }}">/platform/info</a></li>
				<li><a href="{{ URL::to('/platform/stats') }}">/platform/stats</a></li>
				<li><a href="{{ URL::to('/platform/doc') }}">/platform/doc</a></li>
				<li><a href="{{ URL::to('/platform/releasenotes') }}">/platform/releasenotes</a></li>
				<li><a href="{{ URL::to('/platform/support') }}">/platform/support</a></li>
				<li><a href="{{ URL::to('/platform/source') }}">/platform/source</a></li>
				<li><a href="{{ URL::to('/platform/tryme') }}">/platform/tryme</a></li>
				<li><a href="{{ URL::to('/platform/licence') }}">/platform/licence</a></li>
				<li><a href="{{ URL::to('/platform/provenance') }}">/platform/provenance</a></li>
				<li><a href="{{ URL::to('/platform/factsheet') }}">/platform/factsheet</a></li>
			</ul>
		</div>
		<div class="col-md-3">
			<h4>Authentication</h4>
			<ul>
				<li><a href="{{ URL::to('/auth/service/info') }}">/auth/service/info</a></li>
				<li><a href="{{ URL::to('/auth/service/stats') }}">/auth/service/stats</a></li>
				<li><a href="{{ URL::to('/auth/service/doc') }}">/auth/service/doc</a></li>
				<li><a href="{{ URL::to('/auth/service/releasenotes') }}">/auth/service/releasenotes</a></li>
				<li><a href="{{ URL::to('/auth/service/support') }}">/auth/service/support</a></li>
				<li><a href="{{ URL::to('/auth/service/source') }}">/auth/service/source</a></li>
				<li><a href="{{ URL::to('/auth/service/tryme') }}">/auth/service/tryme</a></li>
				<li><a href="{{ URL::to('/auth/service/licence') }}">/auth/service/licence</a></li>
				<li><a href="{{ URL::to('/auth/service/provenance') }}">/auth/service/provenance</a></li>
				<li><a href="{{ URL::to('/auth/service/factsheet') }}">/auth/service/factsheet</a></li>
			</ul>
		</div>
		<div class="col-md-3">
			<h4>Computation</h4>
			<ul>
				<li><a href="{{ URL::to('/computation/service/info') }}">/computation/service/info</a></li>
				<li><a href="{{ URL::to('/computation/service/stats') }}">/computation/service/stats</a></li>
				<li><a href="{{ URL::to('/computation/service/doc') }}">/computation/service/doc</a></li>
				<li><a href="{{ URL::to('/computation/service/releasenotes') }}">/computation/service/releasenotes</a></li>
				<li><a href="{{ URL::to('/computation/service/support') }}">/computation/service/support</a></li>
				<li><a href="{{ URL::to('/computation/service/source') }}">/computation/service/source</a></li>
				<li><a href="{{ URL::to('/computation/service/tryme') }}">/computation/service/tryme</a></li>
				<li><a href="{{ URL::to('/computation/service/licence') }}">/computation/service/licence</a></li>
				<li><a href="{{ URL::to('/computation/service/provenance') }}">/computation/service/provenance</a></li>
				<li><a href="{{ URL::to('/computation/service/factsheet') }}">/computation/service/factsheet</a></li>
			</ul>			
		</div>
	</div>

	<h2>CANARIE links on the remote services</h2>
	@foreach ($rs_list as $rs)
		<div class="row">
			<div class="col-md-6">
				<h4>{{ $rs->name }}: Database</h4>
				<ul>
					<li><a href="{{ $rs->url }}db/service/info">/db/service/info</a></li>
					<li><a href="{{ $rs->url }}db/service/stats">/db/service/stats</a></li>
					<li><a href="{{ $rs->url }}db/service/doc">/db/service/doc</a></li>
					<li><a href="{{ $rs->url }}db/service/releasenotes">/db/service/releasenotes</a></li>
					<li><a href="{{ $rs->url }}db/service/support">/db/service/support</a></li>
					<li><a href="{{ $rs->url }}db/service/source">/db/service/source</a></li>
					<li><a href="{{ $rs->url }}db/service/tryme">/db/service/tryme</a></li>
					<li><a href="{{ $rs->url }}db/service/licence">/db/service/licence</a></li>
					<li><a href="{{ $rs->url }}db/service/provenance">/db/service/provenance</a></li>
					<li><a href="{{ $rs->url }}db/service/factsheet">/db/service/factsheet</a></li>
				</ul>
			</div>

			<div class="col-md-6">
				<h4>{{ $rs->name }}: Database Migration</h4>
				<ul>
					<li><a href="{{ $rs->url }}dbmigration/service/info">/dbmigration/service/info</a></li>
					<li><a href="{{ $rs->url }}dbmigration/service/stats">/dbmigration/service/stats</a></li>
					<li><a href="{{ $rs->url }}dbmigration/service/doc">/dbmigration/service/doc</a></li>
					<li><a href="{{ $rs->url }}dbmigration/service/releasenotes">/dbmigration/service/releasenotes</a></li>
					<li><a href="{{ $rs->url }}dbmigration/service/support">/dbmigration/service/support</a></li>
					<li><a href="{{ $rs->url }}dbmigration/service/source">/dbmigration/service/source</a></li>
					<li><a href="{{ $rs->url }}dbmigration/service/tryme">/dbmigration/service/tryme</a></li>
					<li><a href="{{ $rs->url }}dbmigration/service/licence">/dbmigration/service/licence</a></li>
					<li><a href="{{ $rs->url }}dbmigration/service/provenance">/dbmigration/service/provenance</a></li>
					<li><a href="{{ $rs->url }}dbmigration/service/factsheet">/dbmigration/service/factsheet</a></li>
				</ul>	
			</div>
		</div>
	@endforeach
</div>

@stop

