@extends('template')

@section('title', 'About')

@section('content')
<div class="container page">

	<h1>About iReceptor</h1>

	<div class="row">
		<div class="col-md-8">			

			<h2>Our Mission</h2>

			<p>iReceptor is a distributed data management system and scientific gateway for mining “Next Generation” sequence data from immune responses. The goal of the project is to: improve the design of vaccines, therapeutic antibodies and cancer immunotherapies by integrating Canadian and international data repositories of antibody and T-cell receptor gene sequences.</p>
			<p>iReceptor provides a technology platform that will lower the barrier to immune genetics researchers who need to federate large, distributed, immune genetics data repositories in order to answer complex questions about the immune response. The focus of the iReceptor project is to leverage existing capabilities and technologies to build a new scientific platform for the immune genetics research community.</p>
			<p>To learn more about us visit: <a href="http://ireceptor.org">http://ireceptor.org</a>.</p>
			<p>To ask questions, host a data repository, or say hi, email <a class="email" href="mailto:help@ireceptor.org">help@ireceptor.org</a>.</p>

			<h2>Our Team</h2>

			<h3>Leadership</h3>

			<div class="row">
				<div class="col-md-12">
					<div class="col-md-6">
						<h4>Principal Investigator</h4>
						<p>
							Dr. Felix Breden<br>
							Department of Biological Sciences<br>
							Simon Fraser University
						</p>

						<h4>Technical Lead</h4>
						<p>
							Dr. Brian Corrie<br>
							iReceptor<br>
							Simon Fraser University
						</p>
					</div>
					<div class="col-md-6">
						<h4>Principal Investigator </h4>
						<p>
							Dr. Jamie Scott<br>
							Molecular Biology and Biochemistry<br>
							Simon Fraser University
						</p>
						<h4>Technical Lead/Project Manager</h4>
						<p>
							Dr. Richard Bruskiewich<br>
							STAR Informatics/Delphinai Corporation<br>
							Simon Fraser University
						</p>
					</div>
				</div>
			</div>

			<h3>The Science</h3>

			<div class="row">
				<div class="col-md-12">
					<div class="col-md-6">
						<h4>Bioinformaticist</h4>
						<p>
							Nishanth Marthandan<br>
							Scott Lab, Department of Biological Sciences<br>
							Simon Fraser University
						</p>
						<h4>Data Curator</h4>
						<p>
							Leah Sanchez<br>
							Milde Department of Biological Sciences<br>
							Simon Fraser University
						</p>
					</div>
					<div class="col-md-6">
						<h4>Data Curator</h4>
						<p>
							Emily Barr<br>
							Department of Biological Sciences<br>
							Simon Fraser University
						</p>
					</div>
				</div>
			</div>

			<h3>The Technology</h3>
			<div class="row">
				<div class="col-md-12">
					<div class="col-md-6">
						<h4>Developer (Data Repository/Services)</h4>
						<p>
							Bojan Zimonja<br>
							KEY, Big Data Initative<br>
							Simon Fraser University
						</p>

						<h4>Developer (Data Repository)</h4>
						<p>
							Yang Zhou<br>
							Department of Biological Sciences<br>
							Simon Fraser University
						</p>
					</div>
					<div class="col-md-6">
						<h4>Developer (Gateway/Services)</h4>
						<p>
							Jérôme Jaglale<br>
							KEY, Big Data Initative<br>
							Simon Fraser University
						</p>

						<h4>Gateway Interface Designer</h4>
						<p>
							Frances Breden<br>
							Simon Fraser University
						</p>
					</div>
				</div>
			</div>

			<h3>Collaborators</h3>
			<div class="row">
				<div class="col-md-12">
					<div class="col-md-6">
						<p>
							Dr. Lindsay Cowell<br>
							Department of Clinical Science<br>
							University of Texas, Southwestern
						</p>

						<p>
							Dr. Robert Holt<br>
							Michael Smith Genome Sciences Centre<br>
							BC Cancer Agency
						</p>
					</div>
					<div class="col-md-6">
						<p>
							Dr. Sachdev Sidhu<br>
							Donelly Centre for Cellular + Biomolecular Research<br>
							The University of Toronto
						</p>
						<p>
							Dr. Scott Christley<br>
							Computational Biology<br>
							University of Texas, Southwestern
						</p>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="row credits">
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

	