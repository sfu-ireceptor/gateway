@extends('template')

@section('title', 'About')

@section('content')
<div class="container">

	<div class="row">
		<div class="col-md-12">
			<h1>About</h1>
			<h2>Our Mission</h2>
			<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Duis quis molestie quam, sit amet consequat libero. Fusce eleifend lectus nec fringilla fringilla. Maecenas egestas velit leo, vel interdum risus euismod ut. Phasellus vulputate est eget suscipit volutpat. Phasellus sit amet lacus viverra lacus ultricies fermentum. Cras vel nibh at enim ultrices aliquet. Fusce hendrerit tellus ut enim auctor, vel ultricies mauris sagittis. Aenean et aliquet odio. Aenean dapibus ex et porta convallis. Maecenas ut lectus malesuada, vehicula dolor blandit, luctus neque. </p>

			<h2>Our Team</h2>
			<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Duis quis molestie quam, sit amet consequat libero. Fusce eleifend lectus nec fringilla fringilla. Maecenas egestas velit leo, vel interdum risus euismod ut. Phasellus vulputate est eget suscipit volutpat. Phasellus sit amet lacus viverra lacus ultricies fermentum. Cras vel nibh at enim ultrices aliquet. Fusce hendrerit tellus ut enim auctor, vel ultricies mauris sagittis. Aenean et aliquet odio. Aenean dapibus ex et porta convallis. Maecenas ut lectus malesuada, vehicula dolor blandit, luctus neque. </p>
		</div>
	</div>

	<div class="row footer">
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
			<h4 class="powered">Powered by</h4>
			<a href="https://www.computecanada.ca/" class="compute_canada">
				<img src="/images/logos/compute_canada.png">
			</a>
			<a href="http://agaveapi.co/" class="agave">
				<img src="/images/logos/agave.png">
			</a>
		</div>
		<div class="col-md-4">
			<h4>Developed and Run by</h4>
			<a href="http://www.irmacs.sfu.ca/" class="irmacs">
				<img src="/images/logos/irmacs.png">
			</a>
		</div>
	</div>

</div>
@stop

	