@extends('template')

@section('title', 'Welcome')
 
@section('content')
<div class="container">
	
	<h1>Welcome to iReceptor</h1>

	<div class="row">

		<div class="col-md-6">
			<p>Your account has been successfully created, and we already logged you in.</p>

						<p>
				<a role="button" class="btn btn-primary"  href="/home">
					Go to home page →
				</a>

			</p>

			<p><strong>You should receive an email shortly</strong> with your username, password and the information below:</p>

			<p><a href="https://ireceptor.org/platform/doc">Documentation on how to use the site</a></p>

<p>If the iReceptor Gateway is useful in your research, or if you download and reuse data from the AIRR Data Commons, please cite the
iReceptor and the AIRR Data Commons papers:</p>

<pre>[Corrie et al.] iReceptor: a platform for querying and analyzing
antibody/B-cell and T-cell receptor repertoire data across federated
repositories, Immunological Reviews, Volume 284:24-41 (2018)
http://doi.org/10.1111/imr.12666
</pre>

<pre>[Christley et al.] The ADC API: A Web API for the Programmatic Query of
the AIRR Data Commons, Front. Big Data, 17 June 2020.
https://doi.org/10.3389/fdata.2020.00022</pre>

<p>Suggested wording for a citation that is storing data in the AIRR Data
Commons:</p>

<pre>Study data is available for download from the AIRR
Data Commons [Christley et al.] using the iReceptor Gateway [Corrie et
al.] using Study ID PRJNA00000.</pre>

<p>Suggested wording for an attribution for data reuse:</p>

<pre>Study data was queried and downloaded from the AIRR Data Commons [Christley et al.]
using the iReceptor Gateway [Corrie et al.] on January 1, 2021.</pre>

<p>If you use data from a paper from within the AIRR Data Commons, please also cite that paper. </p>

<p><a href="mailto:support@ireceptor.org.">Let us know</a> if you have questions, problems, or feedback.</p>

		</div>

	</div>

</div>
@stop 
