@extends('template')

@section('title', 'Sequence Search')
@section('sample_query_id', $sample_query_id)

@section('content')

<div class="banner_title sequences">
    <h1>2. Sequence Search</h1>
    <p class="sh1">Filter by sequence and sequence annotation feature</p>
</div>

<div class="container-fluid sequence_container">

    <div class="row">
        <div class="col-md-2 filters">

            <h3 class="first">Filters</h3>

            {{ Form::open(array('url' => 'sequences', 'role' => 'form', 'method' => 'post', 'class' => 'sequence_search standard_sequence_search show_reloading_message')) }}

                @foreach ($hidden_fields as $hf)
                    <input type="hidden" name="{{$hf['name']}}" value="{{$hf['value']}}">
                @endforeach

                <input type="hidden" name="sample_query_id" value="{{ $sample_query_id }}" />

                <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="headingOne">
                            <h4 class="panel-title">
                                <a role="button" class="{{ in_array('0', $open_filter_panel_list) ? '' : 'collapsed' }}" data-toggle="collapse" data-parent="#accordion" href="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                                    Filter by VDJ
                                </a>
                            </h4>
                        </div>
                        <div id="collapseOne" class="panel-collapse collapse {{ in_array('0', $open_filter_panel_list) ? 'in' : '' }}" role="tabpanel" aria-labelledby="headingOne">
                            <div class="panel-body">
                                <div class="form-group">
                                    {{ Form::label('v_call',  __('short.v_call')) }}
                                    @include('help', ['id' => 'v_call'])
                                    {{ Form::text('v_call', '', array('class' => 'form-control', 'data-toggle' => 'tooltip', 'title' => 'Exact match on either family, gene, or allele. A complete family, gene, or allele must be entered or the search will return no results. Will take a long time if millions of sequences are found.', 'data-placement' => 'bottom')) }}
                                </div>

                                <div class="form-group">
                                    {{ Form::label('d_call', __('short.d_call')) }}
                                    @include('help', ['id' => 'd_call'])
                                    {{ Form::text('d_call', '', array('class' => 'form-control', 'data-toggle' => 'tooltip', 'title' => 'Exact match on either family, gene, or allele. A complete family, gene, or allele must be entered or the search will return no results. Will take a long time if millions of sequences are found.', 'data-placement' => 'bottom')) }}
                                </div>

                                <div class="form-group">
                                    {{ Form::label('j_call', __('short.j_call')) }}
                                    @include('help', ['id' => 'j_call'])
                                    {{ Form::text('j_call', '', array('class' => 'form-control', 'data-toggle' => 'tooltip', 'title' => 'Exact match on either family, gene, or allele. A complete family, gene, or allele must be entered or the search will return no results. Will take a long time if millions of sequences are found.', 'data-placement' => 'bottom')) }}
                                </div>

                                <p class="button_container">
                                    {{ Form::submit('Apply filters →', array('class' => 'btn btn-primary search_samples')) }}
                                </p>

                            </div>
                        </div>
                    </div>

                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="headingTwo">
                            <h4 class="panel-title">
                                <a role="button" class="{{ in_array('1', $open_filter_panel_list) ? '' : 'collapsed' }}" data-toggle="collapse" data-parent="#accordion" href="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                    Filter by Junction AA
                                </a>
                            </h4>
                        </div>
                        <div id="collapseTwo" class="panel-collapse collapse {{ in_array('1', $open_filter_panel_list) ? 'in' : '' }}" role="tabpanel" aria-labelledby="headingTwo">
                            <div class="panel-body">
                                <div class="form-group">
                                    {{ Form::label('junction_aa', __('short.junction_aa')) }}
                                    @include('help', ['id' => 'junction_aa'])
                                    {{ Form::text('junction_aa', '', array('class' => 'form-control', 'minlength' => '4', 'data-toggle' => 'tooltip', 'title' => 'Substring search (matches entire substring provided, minimum of 4 AA required). Can take several minutes if millions of sequences are found.', 'data-placement' => 'bottom')) }}
                                </div>

                                @if (isset($conserved_aa_warning))
                                    <p class="bg-warning conserved_aa_warning">
                                        <span class="glyphicon glyphicon-info-sign"></span>
                                        To find all results across ADC/IEDB, remove conserved AAs: <code>{{ $junction_aa_without_conserved_aa }}</code>
                                    </p>
                                @endif

                                @if (isset($iedb_info) && $iedb_info)
                                    <div class="panel panel-primary iedb">
                                        <div class="panel-body">
                                            <p>
                                                <code>{{ $filter_fields['junction_aa'] }}</code>
                                                has known specificity to antigens from the following organisms:
                                            </p>
                                            <ul>
                                                @foreach ($iedb_organism_list as $i => $o)
                                                    <li><span title="{{ $iedb_organism_list_extra[$i] }}">{{ $iedb_organism_list_short[$i] }}</span></li>
                                                @endforeach
                                            </ul>
                                            <p>
                                                <a href="https://www.iedb.org/result_v3.php" class="external" target="_blank">
                                                    Find more information with a <br />"Receptor Search" at IEDB.org
                                                </a>
                                            </p>
                                        </div>
                                    </div>
                                @endif

                                <div class="form-group">
                                    {{ Form::label('junction_aa_length', __('short.ir_junction_aa_length')) }}
                                    {{ Form::text('ir_junction_aa_length', '', array('class' => 'form-control', 'data-toggle' => 'tooltip', 'title' => 'Exact value match. Will take a long time if millions of sequences are found.', 'data-placement' => 'bottom')) }}
                                </div>

                                <p class="button_container">
                                    {{ Form::submit('Apply filters →', array('class' => 'btn btn-primary search_samples')) }}
                                </p>

                            </div>
                        </div>
                    </div>

                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="headingThree">
                            <h4 class="panel-title">
                                <a role="button" class="{{ in_array('2', $open_filter_panel_list) ? '' : 'collapsed' }}" data-toggle="collapse" data-parent="#accordion" href="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                    Advanced filters
                                </a>
                            </h4>
                        </div>
                        <div id="collapseThree" class="panel-collapse collapse {{ in_array('2', $open_filter_panel_list) ? 'in' : '' }}" role="tabpanel" aria-labelledby="headingThree">
                            <div class="panel-body">
                                <div class="form-group">
                                    {{ Form::label('cell_id', __('short.cell_id')) }}
                                    {{ Form::text('cell_id', '', array('class' => 'form-control', 'data-toggle' => 'tooltip', 'title' => 'Exact match - no substrings allowed', 'data-placement' => 'bottom')) }}
                                </div>
                                <div class="form-group">
                                    {{ Form::label('productive', __('short.productive')) }}
                                    {{ Form::select('productive', $functional_list, '', array('class' => 'form-control')) }}
                                </div>
                                <div class="form-group">
                                    {{ Form::label('ir_epitope_ref', __('short.ir_epitope_ref')) }}
                                    {{ Form::text('ir_epitope_ref', '', array('class' => 'form-control', 'data-toggle' => 'tooltip', 'title' => 'Exact match, IEDB CURIE required', 'data-placement' => 'bottom')) }}
                                </div>
                                <div class="form-group">
                                    {{ Form::label('ir_antigen_ref', __('short.ir_antigen_ref')) }}
                                    @include('help', ['id' => 'ir_antigen_ref'])
                                    {{ Form::select('ir_antigen_ref[]', $ir_antigen_ref_ontology_list, '', array('class' => 'form-control multiselect-ui', 'multiple' => 'multiple')) }}
                                </div>
                                <div class="form-group">
                                    {{ Form::label('ir_species_ref', __('short.ir_species_ref')) }}
                                    {{ Form::select('ir_species_ref[]', $ir_species_ref_ontology_list, '', array('class' => 'form-control multiselect-ui', 'multiple' => 'multiple')) }}
                                </div>
                                <div class="form-group">
                                    {{ Form::label('reactivity_ref_rearrangement', __('short.reactivity_ref_rearrangement')) }}
                                    {{ Form::text('reactivity_ref_rearrangement', '', array('class' => 'form-control', 'data-toggle' => 'tooltip', 'title' => 'Exact match, IEDB CURIE required', 'data-placement' => 'bottom')) }}
                                </div>
                                <p class="button_container">
                                    {{ Form::submit('Apply filters →', array('class' => 'btn btn-primary search_samples')) }}
                                </p>
                            </div>
                        </div>
                    </div>

                </div>        

            {{ Form::close() }}                
        </div>

        <div class="col-md-10">
            <div class="reloading_contents">

                <!-- Services which didn't respond -->
                @if ( ! empty($rest_service_list_no_response))
                    <div class="alert alert-warning" role="alert">
                        <p>Sorry, the statistics below are incomplete.</p>
                        @if ( ! empty($rest_service_list_no_response_timeout))
                            <p>These repositories did not return statistics before the Gateway time limit of {{ config('ireceptor.service_request_timeout') }} sec:</p>
                            <ul>
                                @foreach ($rest_service_list_no_response_timeout as $rs)
                                        <li>{{ $rs->display_name }}</li>
                                @endforeach
                            </ul>
                            <p>For accurate statistics, try to reduce the size of the data you're exploring. You can also download the sequences and perform complex analyses offline.</p>
                        @endif

                        @if ( ! empty($rest_service_list_no_response_error))
                            <p>An unexpected error occurred when querying the following repositories:</p>
                            <ul>
                                @foreach ($rest_service_list_no_response_error as $rs)
                                        <li>{{ $rs->name }}</li>
                                @endforeach
                            </ul>
                            <p>Please try again later.</p>
                        @endif
                    </div>
                @endif

                <!-- Active filters -->
                @if ( ! empty($filter_fields) || ! empty($sample_filter_fields))
                    <div class="active_filters">
                        <h3>Active filters</h3>

                        @if ( ! empty($sample_filter_fields))
                            <h4>Repertoire Metadata filters:</h4>
                            @foreach($sample_filter_fields as $filter_key => $filter_value)
                                <span title= "@lang('short.' . $filter_key): {{$filter_value}}", class="label label-default">
                                    @lang('short.' . $filter_key): <span class="value">{{ $filter_value }}</span>
                                </span>
                            @endforeach
                            @isset($sample_query_id)
                                <a href="/samples?query_id=@yield('sample_query_id', '')"class="remove_filters">
                                    Go back to Repertoire Metadata Search
                                </a>
                            @endisset                        
                            <br>
                        @endif

                        @if ( ! empty($filter_fields))
                            <h4>Sequence filters:</h4>
                            @foreach($filter_fields as $filter_key => $filter_value)
                                <a title= "@lang('short.' . $filter_key): {{ $filter_value }}" href="/sequences?query_id={{ $query_id }}&amp;remove_filter={{ $filter_key }}" class="label label-primary">
                                    <span class="glyphicon glyphicon-remove" aria-hidden="true"></span>
                                    @lang('short.' . $filter_key): <span class="value">{{ $filter_value }}</span>
                                </a>
                            @endforeach

                            <a href="/sequences?query_id={{ $query_id }}&amp;remove_filter=all" class="remove_filters">
                                Remove all sequence filters
                            </a>
                        @endif


                        <a class="bookmark" href="/system/" data-uri="{{ $url }}">
                            @if ($bookmark_id)
                                <button type="button" class="btn btn-success" aria-label="Bookmark" data-id="{{ $bookmark_id }}">
                                  <span class="glyphicon glyphicon-star" aria-hidden="true"></span>
                                  <span class="text">Bookmarked</span>
                                </button>
                            @else
                                <button type="button" class="btn btn-primary" aria-label="Bookmark">
                                  <span class="glyphicon glyphicon-star-empty" aria-hidden="true"></span>
                                  <span class="text">Bookmark this search</span>
                                </button>
                            @endif
                        </a>

                    </div>
                @endif    

                @if ($total_filtered_objects <= 0)
                    <!-- No results -->
                    <div class="no_results">
                        <h2>No Results</h2>
                        <p>
                            Remove a filter
                            @isset($no_filters_query_id)
                                or <a href="/sequences?query_id={{ $no_filters_query_id }}">remove all filters</a>
                            @endisset
                            to return results.
                        </p>
                        <p>For more information, go to our <a href="http://ireceptor.org/platform/doc/faq" class="external" target="_blank"> FAQ (Frequently Asked Questions)</a></p>    
                    </div>
                @else
                    <!-- Statistics -->
                    <h3 class="{{ empty($filter_fields) ? 'first' : '' }}">Search results statistics</h3>
                    <div class="statistics">
                        <p>
                            <strong class="summary">
                                <span title="{{ number_format($total_filtered_objects) }}">
                                    {{ number_format($total_filtered_objects) }} sequences
                                </span>
                                ({{ $total_filtered_samples }} {{ str_plural('repertoire', $total_filtered_samples)}})
                            </strong>
                            returned from

                            <a href="#" class="toggle_modal_rest_service_list_folded">
                                {{ $total_filtered_repositories }} remote {{ str_plural('repository', $total_filtered_repositories)}},</a>
                            <a href="#" class="toggle_modal_rest_service_list_expanded">
                                {{ $total_filtered_labs }} research {{ str_plural('lab', $total_filtered_labs)}} and
                                {{ $total_filtered_studies }} {{ str_plural('study', $total_filtered_studies)}}.
                            </a>
                            
                        </p>
                        
                        <!-- repos/labs/studies details popup -->
                        @include('rest_service_list', ['total_repositories' => $total_filtered_repositories, 'total_labs' => $total_filtered_labs, 'total_projects' => $total_filtered_studies, 'tab' => 'sequence'])

                        <div class="charts">
                            <div class="row">
                                <div class="col-md-2 chart" data-chart-data="{!! object_to_json_for_html($charts_data['chart1']) !!}"></div>
                                <div class="col-md-2 chart" data-chart-data="{!! object_to_json_for_html($charts_data['chart2']) !!}"></div>
                                <div class="col-md-2 chart" data-chart-data="{!! object_to_json_for_html($charts_data['chart3']) !!}"></div>
                                <div class="col-md-2 chart" data-chart-data="{!! object_to_json_for_html($charts_data['chart4']) !!}"></div>
                                <div class="col-md-2 chart" data-chart-data="{!! object_to_json_for_html($charts_data['chart5']) !!}"></div>
                                <div class="col-md-2 chart" data-chart-data="{!! object_to_json_for_html($charts_data['chart6']) !!}"></div>
                            </div>
                        </div>
                                    
                    </div>
                @endif 
                
                @if(config('services.tapis.enabled'))
                    <a href="#analysis" class="btn btn-warning pull-right download_sequences">
                        <span class="glyphicon glyphicon-cloud-upload" aria-hidden="true"></span>
                            <span class="text">Run analysis</span>
                    </a>
                @endif

                @if ($total_filtered_objects > 0)
                    @if ($total_filtered_objects > config('ireceptor.sequences_download_limit'))
                        <a href="/sequences-download" class="btn btn-primary pull-right download_sequences" disabled="disabled" role="button" data-container="body" data-toggle="tooltip" data-placement="top" title="Downloads of more than {{ number_format(config('ireceptor.sequences_download_limit')) }} sequences will be possible in the near future." data-trigger="hover" tabindex="0">
                            <span class="glyphicon glyphicon-download-alt" aria-hidden="true"></span>
                            <span class="text">Download all {{number_format($total_filtered_objects)}} sequences</span>
                        </a>
                    @else
                        <a href="/sequences-download?query_id={{ $query_id }}&amp;n={{ $total_filtered_objects }}&amp;page=sequences" class="btn btn-sequences pull-right download_sequences">
                            <span class="glyphicon glyphicon-download-alt" aria-hidden="true"></span>
                            <span class="text">Download all {{number_format($total_filtered_objects)}} sequences <strong>{{ $download_time_estimate ? '(will take up to ' . $download_time_estimate . ')' : ''}}</strong></span>
                        </a>
                    @endif

                    @if (! empty($sequence_list))
                        <h3>
                            Individual Sequences
                            <small class="sequence_count">
                                1-{{ count($sequence_list) }}
                                of
                                <span title="{{ number_format($total_filtered_objects) }}">
                                    {{ human_number($total_filtered_objects) }}
                                </span>
                            </small>

                            <a class="btn btn-xs" data-toggle="collapse" href="#column_selector" aria-expanded="false" aria-controls="column_selector" title="Edit Columns">
                              <span class="glyphicon glyphicon-edit" aria-hidden="true"></span>
                              Customize displayed columns
                            </a>
                        </h3>

                        <!-- table column selector -->
                        @include('columnSelector')


                        <!-- sequence data -->
                        <table class="table table-striped table-condensed much_data table-bordered">
                            <thead>
                                <tr>
                                    @foreach ($field_list as $field)
                                        <th class="text-nowrap col_{{ $field['ir_id'] }} {{ in_array($field['ir_id'], $current_columns) ? '' : 'hidden' }}">
                                            @lang('short.' . $field['ir_id'])
                                            @include('help', ['id' => $field['ir_id']])
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($sequence_list as $s)
                                <tr>
                                    @foreach ($field_list as $field)
                                        <td class="text-nowrap col_{{ $field['ir_id'] }} {{ in_array($field['ir_id'], $current_columns) ? '' : 'hidden' }}">
                                            @isset($s->{$field['ir_id']})
                                                @if($field['ir_id'] == 'functional')
                                                    {{ $s->functional ? 'Yes' : 'No' }}
                                                @elseif($field['ir_id'] == 'v_call' || $field['ir_id'] == 'j_call' || $field['ir_id'] == 'd_call' )
                                                    <span title="{{ $s->{$field['ir_id']} }}">
                                                        {{ str_limit($s->{$field['ir_id']}, $limit = 30, $end = '‥') }}
                                                    </span>
                                                @elseif($field['ir_id'] == 'ir_species_ref' )
                                                    <span title="{{ $s->ir_species_ref_display }}">
                                                        @if( explode(':', $s->{$field['ir_id']})[0] == 'NCBITaxon')
                                                           <a href="http://purl.obolibrary.org/obo/NCBITaxon_{{explode(':', $s->{$field['ir_id']})[1] }}" target="_blank">{{ str_limit($s->ir_species_ref_display, $limit = 30, $end = '‥') }}</a> 

                                                        @else
                                                            {{ str_limit($s->{$field['ir_id']}, $limit = 30, $end = '‥') }}
                                                        @endif
                                                    </span>
                                                @elseif($field['ir_id'] == 'ir_antigen_ref' )
                                                    <span title="{{ $s->ir_antigen_ref_display }}">
                                                        @if( explode(':', $s->{$field['ir_id']})[0] == 'UNIPROT')
                                                           <a href="https://www.uniprot.org/uniprotkb/{{explode(':', $s->{$field['ir_id']})[1] }}" target="_blank">{{ str_limit($s->ir_antigen_ref_display, $limit = 30, $end = '‥') }}</a> 
                                                        @elseif(explode(':', $s->{$field['ir_id']})[0] == 'NCBIPROTEIN')
                                                           <a href="https://www.ncbi.nlm.nih.gov/protein/{{explode(':', $s->{$field['ir_id']})[1] }}" target="_blank">{{ $s->{$field['ir_id']} }}</a> 
                                                        @else
                                                            {{ str_limit($s->{$field['ir_id']}, $limit = 30, $end = '‥') }}
                                                        @endif
                                                    </span>
                                                @elseif($field['ir_id'] == 'ir_epitope_ref' )
                                                    <span title="{{ $s->{$field['ir_id']} }}">
                                                        @if( explode(':', $s->{$field['ir_id']})[0] == 'IEDB_EPITOPE')
                                                           <a href="https://iedb.org/epitope/{{explode(':', $s->{$field['ir_id']})[1] }}" target="_blank">{{ $s->ir_epitope_ref_display }}</a> 
                                                        @else
                                                            {{ str_limit($s->{$field['ir_id']}, $limit = 30, $end = '‥') }}
                                                        @endif
                                                    </span>
                                                @elseif($field['ir_id'] == 'reactivity_ref_rearrangement' )
                                                    <span title="{{ $s->{$field['ir_id']} }}">
                                                        @if( explode(':', $s->{$field['ir_id']})[0] == 'IEDB_RECEPTOR')
                                                           <a href="https://iedb.org/receptor/{{explode(':', $s->{$field['ir_id']})[1] }}" target="_blank">{{ $s->{$field['ir_id']} }}</a> 
                                                        @else
                                                            {{ str_limit($s->{$field['ir_id']}, $limit = 30, $end = '‥') }}
                                                        @endif
                                                    </span>
                                                @else
                                                    @if(is_object($s->{$field['ir_id']}))
                                                        <span title="{{ json_encode($s->{$field['ir_id']}) }}">
                                                            {{ json_encode($s->{$field['ir_id']}) }}
                                                        </span>
                                                    @elseif (is_array($s->{$field['ir_id']}))
                                                        <span title="{{ implode(', ', $s->{$field['ir_id']}) }}">
                                                            {{ str_limit(implode(', ', $s->{$field['ir_id']}), $limit = 25, $end = '‥') }}
                                                        </span>
                                                    @else
                                                        <span title="{{ $s->{$field['ir_id']} }}">
                                                            @if (is_bool($s->{$field['ir_id']}))
                                                                {{ $s->{$field['ir_id']} ? 'Yes' : 'No' }}
                                                            @else
                                                                {{ $s->{$field['ir_id']} }}
                                                            @endif
                                                        </span>
                                                    @endif
                                                @endif
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                    @if(config('services.tapis.enabled'))
                        <h3 id="analysis">
                            <span class="glyphicon glyphicon-cloud-upload" aria-hidden="true"></span>
                            Launch an Analysis Job
                        </h3>
                        
                        <div class="row">
                            <div class="col-md-6">

                                @if ($total_filtered_objects <= config('ireceptor.sequences_download_limit'))
                                    <div role="tabpanel" class="analysis_apps_tabpanel">
                                        <!-- Tab links -->
                                        <ul class="nav nav-tabs analysis-tabs" role="tablist">
                                            @php $count = 0 @endphp
                                            @foreach ($app_list as $app)
                                                @if ( $count === 0)
                                                    <li role="presentation" class="active"><a href="#{{$app['app_tag']}}" aria-controls="{{$app['app_tag']}}" role="tab" data-toggle="tab">{{$app['name']}}</a></li>
                                                @else
                                                    <li role="presentation"><a href="#{{$app['app_tag']}}" aria-controls="{{$app['app_tag']}}" role="tab" data-toggle="tab">{{$app['name']}}</a></li>
                                                @endif
                                                @php $count = $count + 1 @endphp
                                            @endforeach
                                        </ul>

                                        <!-- Tab panes -->
                                        <div class="tab-content">
                                            @php $count = 0 @endphp
                                            @foreach ($app_list as $app)
                                                <div role="tabpanel" class="tab-pane {{ $count === 0 ? 'active' : ''}}" id="{{ $app['app_tag'] }}">
                                                    {{ Form::open(array('url' => 'jobs/launch-app', 'role' => 'form', 'target' => '_blank')) }}
                                                    {{ Form::hidden('filters_json', $filters_json) }}
                                                    {{ Form::hidden('data_url', $url) }}
                                                    {{ Form::hidden('app_id', $app['app_id']) }}
                                                    {{ Form::hidden('n_objects', $total_filtered_objects) }}
                                                    <!-- Application parameters  -->
                                                    <h4>{{ $app['description'] }}</h4>
                                                    <p>{!! $app['info'] !!}</p>

                                                    @foreach ($app['parameter_list'] as $parameter)
                                                          <div class="row"> <div class="col-md-6"> <div class="form-group">
                                                            {{ Form::label($parameter['name'], $parameter['label']) }}
                                                            <span class="help" role="button" data-container="body" data-toggle="popover_form_field" data-placement="right" data-content="<p>{{$parameter['description']}}</p>" data-trigger="hover" tabindex="0">
                                                            <span class="glyphicon glyphicon-question-sign">
                                                            </span></span>
                                                            @if ( ! empty($parameter['choices']) )
                                                                {{ Form::select($parameter['name'], $parameter['choices'], '', array('class' => 'form-control')) }}
                                                            @else
                                                                {{ Form::textarea($parameter['name'], $parameter['default'], array('class' => 'form-control','rows'=>'1')) }}
                                                            @endif
                                                        </div> </div> </div>
                                                    @endforeach
                                                    <!-- Job control parameters  -->
                                                    <!-- For now users can't change these...
                                                    <div class="row"> <div class="col-md-10">
                                                        <h3>Job control parameters
                                                            <span class="help" role="button" data-container="body" data-toggle="popover_form_field" data-placement="right" data-content="<p>Parameters to control job resources used</p>" data-trigger="hover" tabindex="0"> 
                                                            <span class="glyphicon glyphicon-question-sign">
                                                            </span></span>
                                                        </h3>
                                                    </div> </div>
                                                    @foreach ($app['job_parameter_list'] as $job_parameter)
                                                          <div class="row"> <div class="col-md-3"> <div class="form-group">
                                                            {{ Form::label($job_parameter['label'], $job_parameter['name']) }}
                                                            <span class="help" role="button" data-container="body" data-toggle="popover_form_field" data-placement="right" data-content="<p>{{$job_parameter['description']}}</p>" data-trigger="hover" tabindex="0"> 
                                                            <span class="glyphicon glyphicon-question-sign">
                                                            </span></span>

                                                            @if ( ! empty($job_parameter['choices']) )
                                                                {{ Form::select($job_parameter['label'], $job_parameter['choices'], '', array('class' => 'form-control')) }}
                                                            @else
                                                                {{ Form::text($job_parameter['label'], $job_parameter['default'], array('class' => 'form-control')) }}
                                                            @endif
                                                        </div> </div> </div>
                                                    @endforeach
                                                    -->
                                                    @if ( $app['runnable'] )
                                                        @if ( $app['required_time_secs'] > 0 ) 
                                                            <div><p><em>
                                                                Estimated run time = {!! secondsToTime($app['required_time_secs'],2) !!} (not including data federation and queue time)</br>
                                                            </em></p></div>
                                                        @else
                                                            <div><p><em>
                                                                No run time estimate available for this anlaysis.</br>
                                                            </em></p></div>
                                                        @endif
                                                        {{ Form::submit('Submit ' . $app['name'] . ' Analysis Job', array('class' => 'btn btn-primary btn-sequences')) }}
                                                        {{ Form::close() }}
                                                    @else
                                                        <strong>{!! $app['runnable_comment'] !!}</strong>
                                                    @endif
                                                </div> <!-- tabpanel -->
                                                @php $count = $count + 1 @endphp
                                            @endforeach
                                        </div> <!-- tabcontent -->
                                    </div> <!-- analysis_apps_tabpanel -->
                                @else
                                    <p>Sorry, analyses of more than {{ number_format(config('ireceptor.sequences_download_limit')) }} sequences are not permitted.</p>
                                @endif
                            </div>

                            <div class="col-md-1">
                            </div>

                            <div class="col-md-4">
                                <div class="panel panel-default analysis-info">
                                    <div class="panel-heading">About Analysis Jobs</div>
                                      <div class="panel-body">
                                        <p>
                                            Data is retrieved from the repositories, copied to a computational resource, and processed by the corresponding analysis app.
                                            This workflow can be monitored from <a href="/jobs">your jobs page</a>.
                                        </p>
                                        <p>
                                            More details and some screenshots: <a href="https://ireceptor.org/node/204" class="external" target="_blank">Data Analysis and Jobs</a>                                            
                                        </p>

                                        <p>Note: An analysis job <strong>can take multiple hours</strong>, depending on the size of the data and the complexity of the analysis. <strong>Maximum job run time is {!! secondsToTime($max_job_time_secs) !!}.</strong> Run time estimates are based on benchmarked worse case performance, analyses with small numbers of large repertoires may complete significantly faster.</p>

                                      </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @endif
            <div>
        </div>
    </div>
</div>
</div>

@include('reloadingMessage')
@include('loadingMessage')

@stop
