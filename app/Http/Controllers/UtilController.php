<?php

namespace App\Http\Controllers;

use App\Deployment;
use App\Jobs\ProcessJobNotification;
use App\LocalJob;

use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class UtilController extends Controller
{
    // URL Controller function for receiving updates from Tapis
    public function updateJobStatus(Request $request)
    {
        //Log::info('Tapis job status update: job ' . json_encode($request));
        $content = json_decode($request->getContent());
        //Log::info('Tapis job status update: content = ' . json_encode($content));
        $event = $content->event;
        $id = $content->event->subject;
        $data = json_decode($content->event->data);
        if (property_exists($data, 'newJobStatus')) {
            $status = $data->newJobStatus;
            Log::info('Tapis job status update: job ' . $id . ' has status ' . $status);
            $lj = new LocalJob('agave-notifications');

            $lj->user = '[Agave]';

            $lj->description = 'Job ' . $id . ': ' . $status;

            $lj->save();

            // ignore this status because it happens at the same time as FINISHED
            if ($status == 'ARCHIVING_FINISHED') {
                $lj->setFinished();

                return;
            }

            $localJobId = $lj->id;

            // queue as a job (to make sure notifications are processed in order)
            ProcessJobNotification::dispatch($id, $status, $localJobId)->onQueue('agave-notifications');
        } else {
            Log::info('updateJobStatus: Got notification, ignoring: ' . $data->message);
        }
    }

    // called by Stripe web hook
    public function subscriptionCustomerUpdate(Request $request)
    {
        Log::debug('UtilController::subscriptionCustomerUpdate');
        // Track duration of processing
        $start_time = Carbon::now();

        // Get the stripe payload - docs here: 
        //     https://docs.stripe.com/api/customers/object
        //     https://docs.stripe.com/webhooks
        // We use stripe to confirm it is a valid event objec.
        $stripeDataJSON = $request->getContent();
        //Log::info('UtilController::subscriptionCustomerUpdate: ' . $stripeDataJSON);
        try {
            $event = \Stripe\Event::constructFrom(
                json_decode($stripeDataJSON, true)
            );
        } catch(\UnexpectedValueException $e) {
            // Invalid payload
            http_response_code(400);
            return;
        }

        // Get the secret for the webhook
        $stripeWebhookSecret = config('services.stripe.webhook_secret');
        #Log::debug('UtilController::subscriptionCustomerUpdate: web hook secret = '.$stripeWebhookSecret);

        // Get the webhook signature
        // In the docs they use this:
        //     $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        // Other docs use this:
        //     $stripeSignature = $request->header('Stripe-Signature');
        // Get the Stripe API signature for this endpoint
        $stripeSignature = $request->header('Stripe-Signature');
        #Log::debug('UtilController::subscriptionCustomerUpdate: stripe signature = '.$stripeSignature);
        // Construct the data confirming a valid signature.
        try {
            $stripeData = \Stripe\Webhook::constructEvent(
                $stripeDataJSON, $stripeSignature, $stripeWebhookSecret
            );
        } catch(\Stripe\Exception\SignatureVerificationException $e) {
            // Invalid signature
            Log::error('UtilController::subscriptionCustomerUpdate: Webhook error while validating signature.');
            http_response_code(400);
            return;
        }

        // Are we processing a customer create?
        Log::info('UtilController::subscriptionCustomerUpdate: Got a ' . $stripeData->type);
        if ($stripeData->type == 'customer.created') {
            $customerData = $stripeData->data->object;
            Log::info('UtilController::subscriptionCustomerUpdate - Customer create = ' . json_encode($customerData));
            // Get the user based on their email.
            $email = $customerData->email;
            $customerID = $customerData->id;
            $user = User::where('email', $email)->first();
            if ($user == null) {
                // If the user doesn't exist, we need to create them an account
                Log::info('UtilController::subscriptionCustomerUpdate: Creating a user');

                // Generate a random string for a password
                $password = str_random(24);

                $names = explode(' ', $customerData->name, 2);
                $country =  $customerData->address->country;
                // Add the user information to the user database
                $user = User::add($names[0], $names[1], $email, $password, $country, "", "", "Commercial");
                $user->stripe_customer = $customerID;
                $user->save();

                // Send an email to the user about account creation
                $t = [];
                $t['app_url'] = config('app.url');
                $t['first_name'] = $user->first_name;
                $t['username'] = $user->username;
                $t['password'] = $password;
                $t['last_name'] = $user->last_name;
                $t['email'] = $user->email;
                $t['notes'] = $user->notes;
                $t['country'] = $user->country;
                $t['institution'] = $user->institution;
                $t['status'] = $user->status;

                // Email credentials
                try {
                    Mail::send(['text' => 'emails.auth.accountCreated'], $t, function ($message) use ($user) {
                        $message->to($user->email)->subject('iReceptor account');
                    });
                } catch (\Exception $e) {
                    Log::error('UtilController::subscriptionCustomerUpdate - Account creation email delivery failed');
                    Log::error('UtilController::subscriptionCustomerUpdate - ' . $e->getMessage());
                }

                // Send an admin notification email about the new user.
                try {
                    Mail::send(['text' => 'emails.auth.newUser'], $t, function ($message) use ($user) {
                        $message->to(config('ireceptor.email_support'))->subject('New account - ' . $user->first_name . ' ' . $user->last_name);
                    });
                } catch (\Exception $e) {
                    Log::error('UtilController::subscriptionCustomerUpdate - Support email delivery failed');
                    Log::error('UtilController::subscriptionCustomerUpdate - ' . $e->getMessage());
                }
            } else {
                if ($user->stripe_customer == null) {
                    // If this is a new assignment of customer, save the ID
                    Log::info('UtilController::subscriptionCustomerUpdate: Updating customer ID for '.$user->username.' ('.$user->email.')');
                    $user->stripe_customer = $customerID;
                    $user->save();
                } else {
                    // This shouldn't happen, as we should only ever get one customer create
                    // for a given email. If it happens, log it and do nothing.
                    Log::error('UtilController::subscriptionCustomerUpdate: Customer with email ' . $user->email . ' already has customerID ' . $user->stripe_customer . ' ignoring new ID '. $customerID);
                }
            }
        } else if ($stripeData->type == 'customer.updated') {
            #Log::info('UtilController::subscriptionCustomerUpdate - Customer subscription = ' . json_encode($stripeData,JSON_PRETTY_PRINT));
            // Get the customer data
            $customerData = $stripeData->data->object;
            $customerID = $customerData->id;

            // Look up the iRecpetor user based on the customer ID
            // If we can't find it log an error message.
            $user = User::where('stripe_customer', $customerID)->first();
            if ($user == null) {
                Log::error('UtilController::subscriptionCustomerUpdate: Could not find user with stripe customer id ' . $customerID);
                return;
            }

            // Update the data we extract from the stripe record
            $names = explode(' ', $customerData->name, 2);
            $country =  $customerData->address->country;
            $user->first_name = $names[0];
            $user->last_name = $names[1];
            $user->country = $country;
            Log::debug('UtilController::subscriptionCustomerUpdate: Updating ' . $customerID . ', name = ' . $names[0] . ' ' . $names[1] . ', country = ' . $country);

            // Update the user info in the DB
            $user->save();

        } else if ($stripeData->type == 'customer.subscription.created') {
            Log::info('UtilController::subscriptionCustomerUpdate - Customer subscription = ' . json_encode($stripeData,JSON_PRETTY_PRINT));
            $subscriptionData = $stripeData->data->object;
            // Get the user based on their email.
            $customerID = $subscriptionData->customer;
            $user = User::where('stripe_customer', $customerID)->first();
            if ($user == null) {
                Log::error('UtilController::subscriptionCustomerUpdate: Could not find user with stripe customer id ' . $customerID);
                return; 
            }
            if ($user->stripe_customer != $customerID) {
                Log::error('UtilController::subscriptionCustomerUpdate: Customer with email ' . $user->email . ' has customerID ' . $user->stripe_customer . ', does not match '. $customerID.' from stripe payload');
                return; 
            }

            // Check the list of subscription items. We expect only one,
            // print a warning if there is more than one.
            if ($subscriptionData->items->total_count > 1) {
                Log::warn('UtilController::subscriptionCustomerUpdate: subscription has more than one item');
            }

            // Get the first subscription item, we ignore if there is more than one
            $subscriptionItem = $subscriptionData->items->data[0];
            $user->stripe_subscription_start = Carbon::createFromTimestamp($subscriptionItem->current_period_start);
            $user->stripe_subscription_end = Carbon::createFromTimestamp($subscriptionItem->current_period_end);
            $user->save();

        }
        // Compute duration of processing
        $end_time = Carbon::now();
        $duration = $end_time->diffForHumans($start_time);
        #Log::info('UtilController::subscriptionCustomerUpdate - Processing duration: ' . $duration);
    }

    // called by GitHub hook
    public function deploy(Request $request)
    {
        Log::debug('UtilController::deploy');
        $already_running_deployment = Deployment::where('running', 1)->first();
        while ($already_running_deployment != null) {
            sleep(5);
            $already_running_deployment = Deployment::where('running', 1)->first();
        }
        Log::debug('UtilController::deploy - after checking for running');

        $start_time = Carbon::now();

        $deployment = new Deployment;
        $deployment->save();

        $githubPayload = $request->getContent();
        $githubHash = $request->header('X-Hub-Signature');

        $localToken = config('app.deploy_secret');
        Log::debug('UtilController::deploy - local secret = ' . $localToken);
        $localHash = 'sha1=' . hash_hmac('sha1', $githubPayload, $localToken, false);
        Log::debug('UtilController::deploy - githubhash = ' . $githubHash);
        Log::debug('UtilController::deploy - localhash = ' . $localHash);

        // Get the payload from the request, convert it to an object, and extract
        // the payload branch that the push request was on.
        $payload_json = $request->input('payload');
        $payload_obj = json_decode($payload_json);
        //Log::debug('Type is: ' . gettype($payload_json));
        //Log::debug('githubPayload=' . json_encode($payload_obj, JSON_PRETTY_PRINT));

        //  Get the branch that the payload says the commit happened on.
        $payloadRef = $payload_obj->ref;
        Log::debug('UtilController::deploy - Payload ref = ' . $payloadRef);

        // Get the branch that we are supposed to deploy on from the configuration.
        $localRef = config('app.deploy_branch');
        Log::debug('UtilController::deploy - Local ref = ' . $localRef);

        Log::info('-------- Deployment STARTED --------');
        // If the payload hashes are the same and the branches are the same
        // then we deploy, otherwise we ignore the github webhook callback.
        if (hash_equals($githubHash, $localHash) && $payloadRef == $localRef) {
            $root_path = base_path();
            $process = new Process(['./util/scripts/deploy.sh']);
            $process->setWorkingDirectory($root_path);
            $process->setTimeout(180);

            $process->run(function ($type, $buffer) {
                echo $buffer;
                Log::info($buffer);
            });
        } else {
            Log::info('Deployment not performed - hash/branch not correct for this gateway.');
            Log::info('githubHash = ' . $githubHash);
            Log::info('localHash  = ' . $localHash);
            Log::info('localToken = ' . $localToken);
        }
        Log::info('-------- Deployment FINISHED --------');

        $deployment->running = false;
        $deployment->save();

        $end_time = Carbon::now();
        $duration = $end_time->diffForHumans($start_time);
        Log::info('Deployment duration: ' . $duration);
    }
}
