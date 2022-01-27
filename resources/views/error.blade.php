@extends('template')

@section('title', 'Error')

@section('content')
<div class="container">

	<div class="row">

		<div class="col-md-6">
			<h1>Error</h1>
			<h2>{{ $message }}</h2>
			<p>{{ $message2 }}</p>
			<p>If you have any questions, contact us at <a href="mailto:support@ireceptor.org">support@ireceptor.org</a>.</p>
		</div>
	</div>
</div>

</script>

@endsection

