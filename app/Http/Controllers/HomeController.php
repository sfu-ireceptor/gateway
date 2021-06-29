<?php

namespace App\Http\Controllers;

use App\FieldName;
use App\News;
use App\Sample;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        // get count of available data (sequences, samples)
        $username = auth()->user()->username;
        $metadata = Sample::metadata($username);
        $data = $metadata;

        // get list of samples
        $sample_list = Sample::public_samples();
        
        $charts_fields = ['study_type', 'organism', 'disease_diagnosis', 'tissue', 'pcr_target_locus', 'template_class'];
        $data['charts_data'] = Sample::generateChartsData($sample_list, $charts_fields);

        // generate statistics
        $sample_data = Sample::stats($sample_list);
        $data['rest_service_list'] = $sample_data['rs_list'];

        // cell type
        $cell_type_list = [];
        foreach ($metadata['cell_subset'] as $v) {
            $cell_type_list[$v] = $v;
        }
        $data['cell_type_list'] = $cell_type_list;

        // organism
        $subject_organism_list = [];
        $subject_organism_list[''] = 'Any';
        foreach ($metadata['organism'] as $v) {
            $subject_organism_list[$v] = $v;
        }
        $data['subject_organism_list'] = $subject_organism_list;

        // clear any lingering form data
        $request->session()->forget('_old_input');

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
}
