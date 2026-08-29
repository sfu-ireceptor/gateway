<?php

namespace App\Http\Controllers;

use App\FieldName;
use App\News;
use App\RestService;
use App\Sample;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

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

    public function terms()
    {
        $data = [];

        return view('terms', $data);
    }

    public function privacyPolicy()
    {
        $data = [];

        return view('privacyPolicy', $data);
    }

    public function subscriptions()
    {
        $data = [];

        return view('subscriptions', $data);
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

    public function confirmInfo()
    {
        $user = Auth::user();
        $data = [];
        $data['status'] = $user->status;
        $data['email'] = $user->email;

        return view('confirmInfo', $data);
    }

    public function confirmInfoOK()
    {
        $user = Auth::user();
        // Set the user info dates to null, indicating they have not been
        // accepted/confirmed.
        $user->email_confirmed_date = null;
        $user->terms_accepted_date = null;
        $user->privacy_policy_accepted_date = null;
        // Update the user status to denote there Email is unconfirmed.
        $old_status = $user->status;
        $email_tag = '-Email Unconfirmed';
        if (! str_contains($user->status, $email_tag)) {
            $user->status = $user->status . $email_tag;
        }
        // Save the user info in the DB
        $user->save();

        // Send an email to the user with the confirmation token
        $t = [];
        $t['app_url'] = config('app.url');
        $t['first_name'] = $user->first_name;
        $t['username'] = $user->username;
        $t['last_name'] = $user->last_name;
        $t['email'] = $user->email;
        $t['notes'] = $user->notes;
        $t['country'] = $user->country;
        $t['institution'] = $user->institution;
        $t['status'] = $user->status;
        $hashKey = config('app.key');
        $token = hash_hmac('sha256', Str::random(40), $hashKey);
        $t['confirm_link'] = config('app.url') . '/user/confirm-info/' . $token;

        // Add token to DB so that we can track it. We delete previous
        // ones as we only want to track active confirmation requests.
        $table = 'confirm_info';
        DB::table($table)->where('email', $user->email)->delete();
        DB::table($table)->insert([
            'email' => $user->email,
            'token' => $token,
            'created_at' => Carbon::now(),
        ]);

        // Email confirmation link
        try {
            Mail::send(['text' => 'emails.auth.emailConfirmation'], $t, function ($message) use ($user) {
                $message->to($user->email)->subject('Confirmation of iReceptor account email');
            });
        } catch (\Exception $e) {
            Log::error('HomeController::confirmInfoOK - User info confirmation email delivery failed.');
            Log::error('HomeController::confirmInfoOK - ' . $e->getMessage());
        }

        return redirect('home')->with('notification', 'You have been emailed a link to confirm the validity of your email. Your account has temporarily been reduced to limited access until the confirmation link has been activated.');
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
