<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use  App\User;
use  App\NewsletterSubscription;
use Illuminate\Support\Facades\Auth;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Mail;

class NewsletterController extends Controller
{
    
    public function verifySubscription($id, $token){
        if($subscription = NewsletterSubscription::where('id', $id)->where('token',$token)->where('is_confirmed',0)->first()){
            $subscription->is_confirmed = true;
            $subscription->save();

            //TODO send welcome email.

            echo 'Subscription confirmed. Muchas gracias! <a href="'.env('FRONTEND_URL').'">Return to site.</a>';

        }else{
            echo 'Error.';
        }
    }

    public function createSubscription(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email',
            'name' => 'required|integer'
        ]);

        if($request->name != 742){
            return response()->json(['message' => 'Not today, junior.'], 403);
        }

        $subscription = new NewsletterSubscription();
        $subscription->email = $request['email'];
        $subscription->token = str_random(16);
        $subscription->save();

        Mail::send([], [], function ($message) use($subscription) {
            $message->to($subscription->email)
                ->subject('Please confirm your email subscription.')
                ->setBody('
                <p>Hi!</p>
                <p>If you just signed up to receive email updates about ForHumanSake.org, please click the link to below to verify your email address. And if you didn’t just sign up... then quite possibly you have made an internet enemy who is now trying to spam you. 🤷‍♀️</p>
                <p>'. url('/newsletter/verify/'.$subscription->id.'/'.$subscription->token) .'</p>'
                , 'text/html');
        });
        return response()->json([
            'success' => 'Email Sent!'
        ], 200);
    }
    
}