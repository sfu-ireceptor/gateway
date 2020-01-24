<div class="finishing_loading_message">
	<div class="row">
		<div class="col-md-1">
			<div class="loader"></div> 
		</div>

		<div class="col-md-10">
			<h3>Almost done.</h3>
			<p>
				We're compiling repertoire metadata from {{ $total_filtered_samples }} {{ str_plural('repertoire', $total_filtered_samples)}} retrieved from {{ $total_filtered_repositories }} remote {{ str_plural('repository', $total_filtered_repositories)}}.<br />
				This can take up to a minute, depending on your computer.
			</p>
		</div>
	</div>
</div>
