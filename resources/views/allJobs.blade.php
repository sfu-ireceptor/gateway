 @extends('template')

@section('title', 'User Jobs')

@section('content')
<div class="container-fluid">
	<h1>User activity</h1>

	<ul class="nav nav-tabs">
	  <li role="presentation"><a href="/admin/queries">Queries</a></li>
	  <li role="presentation"><a href="/admin/downloads">Downloads</a></li>
	  <li role="presentation" class="active"><a href="/admin/jobs">Jobs</a></li>
	</ul>

	<p></p>

	<table class="table table-bordered table-striped table-condensed job_list">
		<thead>
			<th>User</th>
			<th>Created at</th>
			<th>Updated at</th>
			<th>App</th>
			<th>Query</th>
			<th>Status</th>
			<th>Job ID</th>
			<th>Data location</th>
			<th>Tapis ID</th>
		</thead>
		<tbody>
			@foreach ($job_list as $j)
			<tr>
				<td>{{ $j->username }}</td>
				<td class="text-nowrap">
					<span class="minor">{{ human_date_time($j->created_at, 'D') }}</span>
					{{ human_date_time($j->created_at, 'M j') }}
					<span class="minor">{{ human_date_time($j->created_at, 'H:i') }}</span>
				</td>				
				<td class="text-nowrap">
					<span class="minor">{{ human_date_time($j->updated_at, 'D') }}</span>
					{{ human_date_time($j->updated_at, 'M j') }}
					<span class="minor">{{ human_date_time($j->updated_at, 'H:i') }}</span>
				</td>				
				<td>{{ $j->app }}</td>
                <td>
                    <a href="{{$j->url}}" title="{{ $j->url }}">
                            {{ str_limit(url_path($j->url), $limit = 70, $end = '‥') }}
                    </a>

                </td>
                <td>
                    @if ($j->status == 2)
                        <span class="label label-success">{{ $j->agave_status }}</span>
                    @elseif ($j->status == 3)
                        <span class="label label-danger">{{ $j->agave_status }}</span>
                    @else 
                        <span class="label label-info">{{ $j->agave_status }}</span>
                    @endif

                </td>
				<td><a href="/jobs/view/{{ $j->id }}">{{ $j->id }}</a></td>
				<td>{{ $j->input_folder }}</td>
				<td>{{ $j->agave_id }}</td>
			</tr>
			@endforeach
		</tbody>
	</table>	

</div>
@stop

