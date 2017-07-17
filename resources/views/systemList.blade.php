@extends('template')

@section('title', 'Systems')

@section('content')
<div class="container">
	
	<h1>Systems</h1>

	@if (isset($notification))
	<div class="alert alert-warning alert-dismissible" role="alert">
		<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		{!! $notification !!}
	</div>
	@endif


	@if (count($system_list) > 0)
	<table class="table table-striped system_list">
		<thead>
			<tr>
				<th></th>
				<th>Hostname</th>
				<th class="text-nowrap">SSH username</th>
				<th>SSH key (to install in ~/.ssh/authorized_keys)</th>
				<th>Created</th>
				<th></th>
			</tr>
		</thead>
		<tbody>
			@foreach ($system_list as $system)
			<tr class="{{{ $system->selected ? 'selected' : '' }}}">
				<td>{{ Form::radio('system_selected', $system->id, $system->selected) }}</td>
				<td class="host">{{ $system->host }}</td>
				<td>{{ $system->username }}</td>
				<td>
					{{ Form::text('key', $system->public_key, array('class' => 'form-control public-key', 'placeholder' => '')) }}
				</td>
				<td>{{ $system->created_at->format('M d, Y')  }}</td>
				<td>
					<a href="/systems/delete/{{ $system->id }}">
						<button type="button" class="btn btn-default" aria-label="Delete">
						  <span class="glyphicon glyphicon-trash" aria-hidden="true"></span> Delete
						</button>
					</a>
				</td>
			@endforeach
		</tbody>
	</table>
	@endif

<div class="row">

	<div class="col-md-4">
		<div class="panel panel-info">
		  <div class="panel-heading">
		    <h3 class="panel-title">Add a new system</h3>
		  </div>
		  <div class="panel-body">
			{{ Form::open(array('url' => 'systems/add', 'role' => 'form')) }}

					    <div class="form-group">
							{{ Form::label('host', 'Hostname') }} ex: bugaboo.westgrid.ca
							{{ Form::text('host', '', array('class' => 'form-control', 'placeholder' => '')) }}
						</div>

					    <div class="form-group">
							{{ Form::label('username', 'SSH username') }} ex: john
							{{ Form::text('username', '', array('class' => 'form-control')) }}
						</div>
				{{ Form::submit('Add system', array('class' => 'btn btn-primary')) }}

			{{ Form::close() }}
		  </div>
		</div>
	</div>

	@if (isset($system_selected))
	<div class="col-md-8">
		<div class="panel panel-info">
		  <div class="panel-heading">
		    <h3 class="panel-title">How to install the SSH key</h3>
		  </div>
		  <div class="panel-body">
			<p>To add the SSH key to your system, execute:</p>
<pre class="wrap ssh_key_how_to">mkdir -p ~/.ssh
echo '<strong>{{ $system_selected->public_key }}</strong>' >> ~/.ssh/authorized_keys
</pre>
		  </div>
		</div>
	</div>
	@endif

</div>

</div>
@stop