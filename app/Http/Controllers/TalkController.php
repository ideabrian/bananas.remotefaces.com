<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use  App\User;
use  App\Talk;
use Illuminate\Support\Facades\Auth;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Mail;

class TalkController extends Controller
{
    
    public function startEmailConvo(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email',
            'url' => 'required',
            'name' => 'required|integer'
        ]);

        if($request->name != 819){
            return response()->json(['message' => 'Not today, junior.'], 403);
        }

        Mail::send([], [], function ($message) use($request) {
            $message->to($request['email'])
                ->subject('This is an email.')
                ->cc('patrick@forhumansake.org')
                ->setBody('
                <p>Hi!</p>
                <p>You entered your email address on '.$request->url.' to start a conversation with me.</p>
                <p>So... let’s start!</p>'
                , 'text/html');
        });
        return response()->json([
            'success' => 'Email Sent!'
        ], 200);
    }


    public function claimCall($daily_room_name){
        if($talk = Talk::where('daily_room_name', $daily_room_name)->first()){

            $user = Auth::user();

            $talk->is_answered = true;
            $talk->listener_id = $user->id;
            $talk->save();

            $user->is_available_to_listen = false;
            $user->save();
        }
    }

    public function getLatestUnanswered(){
        if($talk = Talk::where('is_answered', 0)->where('is_initialized', 1)->first()){
            return $talk;
        }else{
            return response()->json(['nothingToAnswer' => true], 200);
        }
    }
    
    public function getAll(){
        $client = new Client();
        $headers = [
            'Authorization' => 'Bearer ' . env('DAILY_KEY'),        
            'Accept'        => 'application/json',
        ];
        $response = $client->request('GET', 'https://api.daily.co/v1/rooms', [
            'headers' => $headers
        ]);
        $body = json_decode($response->getBody());
        print_r($body);
    }
    
    public function start(Request $request)
    {

        try{

            //we're not going to assign a listener at this stage, because we'll put it up for ALL listeners to grab. But we need to make sure that at least one listener exists, otherwise we'll let the user know that we can't help right now.
            if($user = User::where('is_listener', 1)->where('is_available_to_listen',1)->first()){
            
                $client = new Client();
                $headers = [
                    'Authorization' => 'Bearer ' . env('DAILY_KEY'),        
                    'Accept'        => 'application/json',
                ];
                $response = $client->request('POST', 'https://api.daily.co/v1/rooms', [
                    'headers' => $headers
                ]);

                $body = json_decode($response->getBody());

                $talk = new Talk();
                $talk->daily_room_name = $body->name;            
                $talk->save();        

                return $talk->daily_room_name;
            }else{
                return response()->json(['noListenerAvailable' => true], 200);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => 'Not found.'], 404);
        }
    

        
    }

    public function destroy($daily_room_name)
    {

        if($talk = Talk::where('daily_room_name', $daily_room_name)->first()){
            $client = new Client();
            $headers = [
                'Authorization' => 'Bearer ' . env('DAILY_KEY'),        
                'Accept'        => 'application/json',
            ];
            $response = $client->request('DELETE', 'https://api.daily.co/v1/rooms/'. $daily_room_name, [
                'headers' => $headers
            ]);
            $talk->delete();
        }
    }

    public function initialize($daily_room_name)
    {

        if($talk = Talk::where('daily_room_name', $daily_room_name)->first()){
            $talk->is_initialized = true;
            $talk->save();
        }
    }
    
}