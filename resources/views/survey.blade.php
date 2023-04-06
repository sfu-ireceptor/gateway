@extends('template')

@section('title', 'iReceptor Survey')

@section('content')
<div class="container">

<div class="row">
	<div class="col-md-3">
	</div>
	<div class="col-md-6 survey_message">

		<p>Dear valued iReceptor users,</p>

		<p>We want your help shaping the future of iReceptor. As you know, our platform is dedicated to facilitating the discovery, sharing, and analysis of Adaptive Immune Receptor Repertoire sequencing (AIRR-seq) data in the AIRR Data Commons. By bringing together antibody/B-cell and T-cell receptor repertoires from multiple studies, labs, and institutions, we are facilitating the advancement of the development of vaccines, therapeutic antibodies against autoimmune diseases, and cancer immunotherapies.</p>

		<p>As a grant funded research group, sustaining the iReceptor Platform operationally is very challenging, in particular with the growing use that we continue to see. We have created a survey to gather feedback on which features you value the most and which mechanisms might be practical to help with the sustainability of the platform.We understand that your time is valuable, but we urge you to take the time to complete this survey. Your input will be instrumental in shaping the future of iReceptor and ensuring that we can continue to provide this valuable resource to the research community.</p>

		<p>Thank you for your continued support of iReceptor. We look forward to hearing from you.</p>

		<p>
			Sincerely,<br>
			The iReceptor team
		</p>

		<div class="announcement" role="alert">
			<p>
				<a  class="btn btn-success external"  target="_blank" role="button" href="/ireceptor-survey-go">Take the iReceptor Survey</a><br>
				<a href="/home">Skip for now, go the Gateway</a>
			</p>
		</div>

	</div>
	<div class="col-md-3">
	</div>
</div>
	
</div>
@stop


