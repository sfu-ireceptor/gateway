<?php

namespace App\Http\Controllers;

use App\FieldName;
use App\News;
use App\RestService;
use App\Sample;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $cached_data = Cache::get('home-data');
        // Uncomment the following if you need to force cache rebuild.
        //$cached_data = null;
        if ($cached_data != null) {
            return view('home', $cached_data);
        }

        // get count of available data (sequences, samples)
        $username = auth()->user()->username;
        $metadata = Sample::metadata($username);
        $data = $metadata;

        // Prepare the data for the pie charts for sequences, clones, cells.
        //
        // Get list cached sequence samples
        //
        $sample_list = Sample::public_samples('sequence');
        // Fields we want to graph. The UI/blade expects six fields
        $charts_fields = ['study_type_id', 'organism', 'disease_diagnosis_id',
            'tissue_id', 'pcr_target_locus', 'template_class', ];
        // Mapping of fields to display as labels on the graph for those that need
        // mappings. These are usually required for ontology fields where we want
        // to aggregate on the ontology ID but display the ontology label.
        $field_map = ['study_type_id' => 'study_type',
            'disease_diagnosis_id' => 'disease_diagnosis',
            'tissue_id' => 'tissue', ];
        // Generate the graph
        $data['charts_data'] = Sample::generateChartsData($sample_list, $charts_fields, $field_map);

        // Generate the rest service list info for this query. This has the
        // sample tree info required for our study browsing.
        $sample_data = Sample::stats($sample_list);
        $data['rest_service_list_sequences'] = $sample_data['rs_list'];

        //
        // Get the cached clone samples
        //
        $sample_list = Sample::public_samples('clone');

        // Fields we want to graph. The UI/blade expects six fields
        $charts_fields = ['study_type_id', 'organism', 'disease_diagnosis_id',
            'tissue_id', 'pcr_target_locus', 'template_class', ];
        // Mapping of fields to display as labels on the graph for those that need
        // mappings. These are usually required for ontology fields where we want
        // to aggregate on the ontology ID but display the ontology label.
        $field_map = ['study_type_id' => 'study_type',
            'disease_diagnosis_id' => 'disease_diagnosis',
            'tissue_id' => 'tissue', ];
        $data['clone_charts_data'] = Sample::generateChartsData($sample_list, $charts_fields, $field_map, 'ir_clone_count');

        // Generate the rest service list info for this query. This has the
        // sample tree info required for our study browsing.
        $sample_data = Sample::stats($sample_list, 'ir_clone_count');
        $data['rest_service_list_clones'] = $sample_data['rs_list'];

        //
        // Get the cached cell samples
        //
        $sample_list = Sample::public_samples('cell');

        // Fields we want to graph. The UI/blade expects six fields
        $charts_fields = ['disease_diagnosis_id', 'tissue_id', 'cell_subset',
            'disease_diagnosis_id', 'tissue_id', 'cell_subset'];
        // Mapping of fields to display as labels on the graph for those that need
        // mappings. These are usually required for ontology fields where we want
        // to aggregate on the ontology ID but display the ontology label.
        $field_map = ['disease_diagnosis_id' => 'disease_diagnosis',
            'tissue_id' => 'tissue', ];
        $data['cell_charts_data'] = Sample::generateChartsData($sample_list, $charts_fields, $field_map, 'ir_cell_count');

        // Generate the rest service list info for this query. This has the
        // sample tree info required for our study browsing.
        $sample_data = Sample::stats($sample_list, 'ir_cell_count');
        $data['rest_service_list_cells'] = $sample_data['rs_list'];

        // Temporarily store this the old way. This should not be required.
        $data['rest_service_list'] = $data['rest_service_list_sequences'];

        //
        // Prepare the data for the menus for sequence quick search box
        //
        // cell type
        $cell_type_ontology_list = [];
        foreach ($metadata['cell_subset_id'] as $v) {
            $cell_type_ontology_list[$v['id']] = $v['label'] . ' (' . $v['id'] . ')';
        }
        $data['cell_type_ontology_list'] = $cell_type_ontology_list;

        // organism ontology info
        $subject_organism_ontology_list = [];
        foreach ($metadata['organism_id'] as $v) {
            $subject_organism_ontology_list[$v['id']] = $v['label'] . ' (' . $v['id'] . ')';
        }
        $data['subject_organism_ontology_list'] = $subject_organism_ontology_list;

        // Check for a banner message for the home page.
        $data['home_banner_display'] = config('ireceptor.home_banner_display');
        $data['home_banner_text'] = config('ireceptor.home_banner_text');

        // clear any lingering form data
        $request->session()->forget('_old_input');

        Cache::put('home-data', $data);

        return view('home', $data);
    }

    public function about()
    {
        return view('about');
    }

    public function news()
    {
        $news_list = News::orderBy('created_at', 'desc')->get();

        $data = [];
        $data['news_list'] = $news_list;

        return view('news', $data);
    }

    public function fieldsDefinitions()
    {
        $data = [];

        // get sample fields grouped
        $sample_field_list_grouped = FieldName::getSampleFieldsGrouped();
        $data['sample_field_list_grouped'] = $sample_field_list_grouped;

        // get sequence fields grouped
        $sequence_field_list_grouped = FieldName::getSequenceFieldsGrouped();
        $data['sequence_field_list_grouped'] = $sequence_field_list_grouped;

        return view('fieldsDefinitions', $data);
    }

    public function repositories()
    {
        $rs_list = RestService::findEnabledPublic();

        // count studies for each repository
        $sample_data = Sample::find([], 'titi');
        foreach ($rs_list as $i => $rs) {
            $rs_list[$i]->nb_studies = 0;
            foreach ($sample_data['rs_list'] as $rs_data) {
                if ($rs_data['rs_id'] == $rs->id) {
                    $rs_list[$i]->nb_studies += $rs_data['total_studies'];
                } elseif ($rs_data['rs_group_code'] != null && $rs_data['rs_group_code'] == $rs->rest_service_group_code) {
                    $rs_list[$i]->nb_studies += $rs_data['total_studies'];
                }
            }
        }

        $data = [];
        $data['rs_list'] = $rs_list;

        return view('repositories', $data);
    }

    public function survey()
    {
        return view('survey');
    }

    public function surveyGo()
    {
        $user = Auth::user();
        $user->did_survey = true;
        $user->save();

        return redirect('https://www.surveymonkey.ca/r/TVCQJXB');
    }
}
