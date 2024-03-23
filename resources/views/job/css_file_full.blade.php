@extends('template')

 
@section('content')
<div class="container job_container">
<!--
    <img src="{{ route('job.show', ['directory' => 'ipa1.ireceptor.org/662', 'filename' => 'ipa1.ireceptor.org_PRJEB8745_BS4IgG_LAMGC1d22_BS4IgG_LAMGC1d22_spleen_IGH-junction_aa_length-histogram.png', 'id' => $job->id]) }}" alt="Example Image">
    {{ route('job.show', ['directory' => $directory, 'filename' => $filename, 'id' => $job->id]) }}


    {{ asset('app/public/test.png') }}
-->
<!--
    <img src="{{ asset('storage/test.png') }}" alt="Example Image">
-->
    @if (count($plain_file) > 0)
        @foreach ($plain_file as $line)
	    {!! $line !!}
<!--
	    {!! $storage_file !!}
-->
            <!-- {{ str_replace('img src="', 'img src="' . $base_directory . '/' . $file_name, $line) }}<br> -->
        @endforeach
    @else
	File is empty
    @endif
</div>
@stop
