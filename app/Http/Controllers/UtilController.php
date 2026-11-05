<?php

namespace App\Http\Controllers;

use App\Deployment;
use App\Jobs\ProcessJobNotification;
use App\LocalJob;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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
            abort(400,'Invalid payload');
        }

        // Get the secret for the webhook
        $stripeWebhookSecret = config('services.stripe.webhook_secret');
        //Log::debug('UtilController::subscriptionCustomerUpdate: web hook secret = '.$stripeWebhookSecret);

        // Get the webhook signature
        // In the docs they use this:
        //     $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        // Other docs use this:
        //     $stripeSignature = $request->header('Stripe-Signature');
        // Get the Stripe API signature for this endpoint
        $stripeSignature = $request->header('Stripe-Signature');
        //Log::debug('UtilController::subscriptionCustomerUpdate: stripe signature = '.$stripeSignature);
        // Construct the data confirming a valid signature.
        try {
            $stripeData = \Stripe\Webhook::constructEvent(
                $stripeDataJSON, $stripeSignature, $stripeWebhookSecret
            );
        } catch(\Stripe\Exception\SignatureVerificationException $e) {
            // Invalid signature
            Log::error('UtilController::subscriptionCustomerUpdate: Webhook error while validating signature.');
            abort(400, 'Webhook error while validating signature');
        }

        // Are we processing a customer create?
        Log::info('UtilController::subscriptionCustomerUpdate: Got a ' . $stripeData->type);
        if ($stripeData->type == 'customer.created') {
            $customerData = $stripeData->data->object;
            Log::info('UtilController::subscriptionCustomerUpdate - Customer create = ' . json_encode($customerData));
            
            // Get the stripe customer ID
            $customerID = $customerData->id;

            // Get the user based on their email.
            $email = $customerData->email;
            // If there is no email, then we can not create a user. 
            // Stripe should never provide a customer without an email.
            if ($email == null) {
                Log::error('UtilController::subscriptionCustomerUpdate: Stripe email not provided for customer id ' . $customerID);
                abort(400,'Email not provided for customer id');
            }
            
            // Get associated iReceptor user based on email.
            $user = User::where('email', $email)->first();
            if ($user == null) {
                // If the user doesn't exist, we need to create them an account
                Log::info('UtilController::subscriptionCustomerUpdate: Creating a user');

                // Generate a random string for a password
                $password = str_random(24);

                // Get the name, handle edge case when name not provided
                if ($customerData->name != null) {
                    $names = explode(' ', $customerData->name, 2);
                } else {
                    $names[0] = "";
                    $names[1] = "";
                }

                // Get the address, handle the edge case when the address is not provided
                if ($customerData->address != null) {
                    $country = $customerData->address->country;
                } else {
                    $country = "";
                }
                // Add the user information to the user database
                $user = User::add($names[0], $names[1], $email, $password, $country, '', '', 'Limited');
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
                    // If this is a new assignment of customer subscription, save the ID
                    Log::info('UtilController::subscriptionCustomerUpdate: Setting customer ID for ' . $user->username . ' (' . $user->email . ')');
                    $user->stripe_customer = $customerID;
                    // Set the user status to Limited - customer.created event doesn't provide
                    // any subscription information.
                    $user->status = "Limited";
                    // Ensure that the user re-confirms their email when they log in. 
                    // Stripe ensures email is valid, need to ensure that the email
                    // on the gateway is the same and also valid. Otherwise we could
                    // be giving a subscription to the wrong user.
                    $user->email_confirmed_date = null;
                    $user->save();
                } else {
                    // This shouldn't happen, as we should only ever get one customer create
                    // for a given email. If it happens, log it and do nothing.
                    Log::error('UtilController::subscriptionCustomerUpdate: Customer with email ' . $user->email . ' already has customerID ' . $user->stripe_customer . ' ignoring new ID ' . $customerID);
                }
            }
        } elseif ($stripeData->type == 'customer.updated') {
            // We have received a customer update call back

            //Log::info('UtilController::subscriptionCustomerUpdate - Customer subscription = ' . json_encode($stripeData,JSON_PRETTY_PRINT));
            // Get the customer data
            $customerData = $stripeData->data->object;
            $customerID = $customerData->id;

            // Look up the iRecpetor user based on the customer ID
            // If we can't find it log an error message.
            $user = User::where('stripe_customer', $customerID)->first();
            if ($user == null) {
                Log::error('UtilController::subscriptionCustomerUpdate: Could not find user with stripe customer id ' . $customerID);
                abort(400,'Could not find user using customer id');
            }

            // Update the data we extract from the stripe record
            // Get the name, handle edge case when name not provided
            if ($customerData->name != null) {
                $names = explode(' ', $customerData->name, 2);
            } else {
                $names[0] = "";
                $names[1] = "";
            }

            // Get the address, handle the edge case when the address is not provided
            if ($customerData->address != null) {
                $country = $customerData->address->country;
            } else {
                $country = "";
            }
            $user->first_name = $names[0];
            $user->last_name = $names[1];
            $user->country = $country;
            Log::debug('UtilController::subscriptionCustomerUpdate: Updating ' . $customerID . ', name = ' . $names[0] . ' ' . $names[1] . ', country = ' . $country);

            // Update the user info in the DB
            $user->save();
        } elseif ($stripeData->type == 'customer.subscription.created') {
            Log::info('UtilController::subscriptionCustomerUpdate - Customer subscription = ' . json_encode($stripeData, JSON_PRETTY_PRINT));
            $subscriptionData = $stripeData->data->object;
            // Get the user based on their email.
            $customerID = $subscriptionData->customer;
            $user = User::where('stripe_customer', $customerID)->first();
            if ($user == null) {
                Log::error('UtilController::subscriptionCustomerUpdate: Could not find user with stripe customer id ' . $customerID);
                abort(400,'Could not find user using customer id');
            }
            if ($user->stripe_customer != $customerID) {
                Log::error('UtilController::subscriptionCustomerUpdate: Customer with email ' . $user->email . ' has customerID ' . $user->stripe_customer . ', does not match ' . $customerID . ' from stripe payload');
                abort(400, 'Email/Customer ID does not match');
            }

            // Check the list of subscription items. We expect only one,
            // print a warning if there is more than one.
            if ($subscriptionData->items->total_count > 1) {
                Log::warn('UtilController::subscriptionCustomerUpdate: subscription has more than one item');
            }

            // Get the first subscription item, we ignore if there is more than one
            $subscriptionItem = $subscriptionData->items->data[0];
            // Get the subscription product being updated
            $stripeProduct = $subscriptionItem->plan->product;
            Log::info('UtilController::subscriptionCustomerUpdate - Stripe product = ' . $stripeProduct);
            // Get the stripe product list as JSON string into a dictionary
            $stripeDictionary = json_decode(config('services.stripe.products'));
            Log::info('UtilController::subscriptionCustomerUpdate - Stripe dictionary = ' . json_encode($stripeDictionary));

            // Update the user access status based on their product. Product
            // list is defined in the environment file for the App.
            if (property_exists($stripeDictionary, $stripeProduct)) {
                $user->status = $stripeDictionary->$stripeProduct;
            } else {
                $user->status = 'Limited-Approval Pending';
                Log::error('UtilController::subscriptionCustomerUpdate: Unknown Stripe product ' . $stripeProduct);
            }

            // If the account is an academic account, check to see if the email
            // is a known/computable academic email address.
            if ($user->status == 'Academic') {
                // Pattern matches string that has an @ as part of the email,
                // has either a .edu.xxx or .ac.xxx compontent, where xxx is a
                // country domain such as edu.au. It also recognizes a .edu email
                // with no country at the end for the US case.
                // This does not recognize academic domains in countries that do
                // not use the .edu. or .ac. format, such as Canada and Germany
                $edu_regex = '/@[^@]*\.(edu|ac)(\.[a-z]{2,})?$/i';
                // If it is not an academic email then it requires approval
                if (!preg_match($edu_regex, $user->email)) {
                    // Set status to requiring approval
                    $user->status = 'Academic-Approval Pending';
                    // Set email confirmation to be true
                    $user->email_confirmed_date = null;
                    Log::info('UtilController::subscriptionCustomerUpdate - Academic user approval required, status = ' . $user->status); 
                }
            }
            Log::info('UtilController::subscriptionCustomerUpdate - user status = ' . $user->status); 

            // Set the user subscription start and end.
            $user->stripe_subscription_start = Carbon::createFromTimestamp($subscriptionItem->current_period_start);
            $user->stripe_subscription_end = Carbon::createFromTimestamp($subscriptionItem->current_period_end);
            Log::info('UtilController::subscriptionCustomerUpdate - start = ' . $subscriptionItem->current_period_start . ' end = ' . $subscriptionItem->current_period_end); 
            $user->save();
        } elseif ($stripeData->type == 'customer.subscription.updated') {
            Log::info('UtilController::subscriptionCustomerUpdate - Customer subscription = ' . json_encode($stripeData, JSON_PRETTY_PRINT));
            $subscriptionData = $stripeData->data->object;

            // Get the user based on their stripe customer ID.
            $customerID = $subscriptionData->customer;
            $user = User::where('stripe_customer', $customerID)->first();
            if ($user == null) {
                Log::error('UtilController::subscriptionCustomerUpdate: Could not find user with stripe customer id ' . $customerID);
                abort(400,'Could not find user using customer id');
            }

            // Check the list of subscription items. We expect only one,
            // print a warning if there is more than one.
            if ($subscriptionData->items->total_count > 1) {
                Log::warn('UtilController::subscriptionCustomerUpdate: subscription has more than one item');
            }

            // Get the first subscription item, we ignore if there is more than one
            $subscriptionItem = $subscriptionData->items->data[0];
            // Get the subscription product being updated
            $stripeProduct = $subscriptionItem->plan->product;
            Log::info('UtilController::subscriptionCustomerUpdate - Stripe product = ' . $stripeProduct);
            // Get the stripe product list as JSON string into a dictionary
            $stripeDictionary = json_decode(config('services.stripe.products'));
            Log::info('UtilController::subscriptionCustomerUpdate - Stripe dictionary = ' . json_encode($stripeDictionary));

            // Update the user access status based on their product. Product
            // list is defined in the environment file for the App.
            Log::info('UtilController::subscriptionCustomerUpdate - user ' . $user->stripe_customer . ' status = ' . $user->status); 
            if (property_exists($stripeDictionary, $stripeProduct)) {
                $user->status = $stripeDictionary->$stripeProduct;
                Log::info('UtilController::subscriptionCustomerUpdate - Setting user status to ' . $user->status); 
            }

            // Get the start and end of the subscription and update it.
            $user->stripe_subscription_start = Carbon::createFromTimestamp($subscriptionItem->current_period_start);
            $user->stripe_subscription_end = Carbon::createFromTimestamp($subscriptionItem->current_period_end);
            Log::info('UtilController::subscriptionCustomerUpdate - updating subscription for user ' . $user->username . ' from ' . $user->stripe_subscription_start . ' to ' . $user->stripe_subscription_end);
            $user->save();
        }
        // Compute duration of processing
        $end_time = Carbon::now();
        $duration = $end_time->diffForHumans($start_time);
        //Log::info('UtilController::subscriptionCustomerUpdate - Processing duration: ' . $duration);
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
