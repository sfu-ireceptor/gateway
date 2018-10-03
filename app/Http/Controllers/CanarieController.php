<?php

namespace App\Http\Controllers;

use App\Job;
use App\User;
use App\Agave;
use App\Stats;
use Carbon\Carbon;
use App\RestService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class CanarieController extends Controller
{
    public function links()
    {
        $data = [];

        $rs_list = RestService::all();
        $data['rs_list'] = $rs_list;

        return view('canarie/links', $data);
    }

    public function linkPage($page)
    {
        $url = 'http://ireceptor.irmacs.sfu.ca/platform/' . $page;

        if ($page == 'factsheet') {
            $url = 'http://www.canarie.ca/software/platforms/ireceptor/';
        } elseif ($page == 'provenance' || $page == 'licence') {
            $url = 'http://ireceptor.irmacs.sfu.ca/platform/doc';
        }

        $data = [];
        $data['title'] = '/' . $page;
        $data['page'] = $page;
        $data['url'] = $url;

        return view('canarie/link', $data);
    }

    public function platformInfo(Request $request, Response $response)
    {
        $t = [];

        $t['name'] = 'iReceptor Gateway';
        $t['synopsis'] = 'A Distributed Data Management System and Scientific Gateway for Mining Next Generation Sequence Data from Immune Responses';
        $t['version'] = '0.1';
        $t['institution'] = 'IRMACS/Simon Fraser University';
        $d = new Carbon('first day of July 2015', 'UTC');
        $t['releaseTime'] = $d->toDateString() . 'T' . $d->toTimeString() . 'Z';
        $t['researchSubject'] = 'Immunology';
        $t['supportEmail'] = 'help@irmacs.sfu.ca';
        $t['tags'] = ['immunology', 'iReceptor'];

        if ($request->wantsJson()) {
            return response()->json($t);
        } else {
            return view('canarie/info', $t);
        }
    }

    public function authInfo(Request $request, Response $response)
    {
        $t = [];

        $t['name'] = 'iReceptor Authentication Service';
        $t['category'] = 'User Management/Authentication';
        $t['synopsis'] = 'iReceptor Authentication Service';
        $t['version'] = '0.1';
        $t['institution'] = 'IRMACS/Simon Fraser University';
        $d = new Carbon('first day of July 2015', 'UTC');
        $t['releaseTime'] = $d->toDateString() . 'T' . $d->toTimeString() . 'Z';
        $t['researchSubject'] = 'Immunology';
        $t['supportEmail'] = 'help@irmacs.sfu.ca';
        $t['tags'] = ['immunology', 'iReceptor'];

        if ($request->wantsJson()) {
            return response()->json($t);
        } else {
            return view('canarie/info', $t);
        }
    }

    public function computationInfo(Request $request, Response $response)
    {
        $t = [];

        $t['name'] = 'iReceptor Computation Service';
        $t['category'] = 'Data Manipulation';
        $t['synopsis'] = 'iReceptor Computation Service';
        $t['version'] = '0.1';
        $t['institution'] = 'IRMACS/Simon Fraser University';
        $d = new Carbon('first day of July 2015', 'UTC');
        $t['releaseTime'] = $d->toDateString() . 'T' . $d->toTimeString() . 'Z';
        $t['researchSubject'] = 'Immunology';
        $t['supportEmail'] = 'help@irmacs.sfu.ca';
        $t['tags'] = ['immunology', 'iReceptor'];

        if ($request->wantsJson()) {
            return response()->json($t);
        } else {
            return view('canarie/info', $t);
        }
    }

    public function platformStats(Request $request, Response $response)
    {
        $t = [];

        $s = Stats::currentStats();

        $t['nbRequests'] = $s->nb_requests;
        $t['lastReset'] = $s->startDateIso8601();

        if ($request->wantsJson()) {
            return response()->json($t);
        } else {
            $t['name'] = 'iReceptor Gateway Stats';
            $t['key'] = 'Number of requests';
            $t['val'] = $s->nb_requests;

            return view('canarie/stats', $t);
        }
    }

    public function authStats(Request $request, Response $response)
    {
        $agave = new Agave;
        if (! $agave->isUp()) {
            app()->abort(503, 'iReceptor Authentication Service is down.');
            Log::debug('iReceptor Authentication Service is down.');
        }

        $t = [];

        $t['nbUsers'] = User::count();
        $d = new Carbon('last day of June 2015', 'UTC');
        $t['lastReset'] = $d->toDateString() . 'T' . $d->toTimeString() . 'Z';

        if ($request->wantsJson()) {
            return response()->json($t);
        } else {
            $t['name'] = 'iReceptor Authentication Service Stats';
            $t['key'] = 'Number of users';
            $t['val'] = $t['nbUsers'];

            return view('canarie/stats', $t);
        }
    }

    public function computationStats(Request $request, Response $response)
    {
        $agave = new Agave;
        if (! $agave->isUp()) {
            app()->abort(503, 'iReceptor Computation Service is down.');
        }

        $t = [];

        $t['nbJobs'] = Job::count();
        $d = new Carbon('last day of June 2015', 'UTC');
        $t['lastReset'] = $d->toDateString() . 'T' . $d->toTimeString() . 'Z';

        if ($request->wantsJson()) {
            return response()->json($t);
        } else {
            $t['name'] = 'iReceptor Commputation Service Stats';
            $t['key'] = 'Number of jobs';
            $t['val'] = $t['nbJobs'];

            return view('canarie/stats', $t);
        }
    }
}
