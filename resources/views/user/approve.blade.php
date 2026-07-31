@extends('template')

@section('title', 'Approve User')

@section('content')
<div class="container">
	
	<h1>Approve User</h1>

	@if (isset($notification))
	<div class="alert alert-warning alert-dismissible" role="alert">
		<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		{!! $notification !!}
	</div>
	@endif


	<div class="row">

		<div class="col-md-6">
            <p>Change user subscription status for {{$first_name}} {{$last_name}} with username {{$username}} from <strong>{{$status}}</strong> to <strong>{{$new_status}}</strong>?</p>
            <div>
            {{ Form::open(array('url' => 'admin/approve-user', 'role' => 'form')) }}
                <input type="hidden" name="status" value="{{ $new_status }}">
                <input type="hidden" name="id" value="{{ $id }}">
				{{ Form::submit('Approve', array('class' => 'btn btn-primary')) }}
			{{ Form::close() }}
            </div>
		</div>

	</div>

</div>
@stop
