<?php

namespace App;
use  App\User;
use  App\Room;
use Illuminate\Support\Facades\Mail;

class Helper
{

    public static function getRoomIDFromRequest(){
        $domain = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);

        if (strpos($domain, 'localhost') !== false) {
            $room = Room::where('slug','localhost')->first();
        }
        else if (strpos($domain, '.remotefaces.com') !== false || strpos($domain, '.face.com') !== false) {
            $subdomain = explode(".",$domain);            
            $room = Room::where('slug',$subdomain)->first();

        }else{
            $room = Room::where('domain',$domain)->first();
        }

        if($room){
            return $room->id;
        }else{
            return null;
        }

    }

    public static function getDomainFromRoom($room){
        if($room->domain){
            return $room->domain;
        }else{
            return $room->slug.'.remotefaces.com';
        }
    }

    public static function sendLoginLink($user_id, $room_id){
        $user = User::find($user_id);
        $room = Room::find($room_id);
        if($user && $room){

            $user->token = str_random(30);
            $user->save();            

            Mail::send([], [], function ($message) use($user, $room) {
                $message->to($user->email)
                    ->subject('Login link for '. $room->name)
                    ->setBody('
                    <p>Here’s your login link. Go nuts!</p>
                    <p>https://'.self::getDomainFromRoom($room).'/login/'.$user->id.'/'.$user->token.'</p>'
                    , 'text/html');
            });
        }
        
    }
}

