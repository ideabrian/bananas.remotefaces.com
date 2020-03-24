<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use  App\User;
use  App\Room;
use  App\RoomUser;
use  App\File;
use  App\Session;
use Illuminate\Support\Facades\Auth;

class RoomController extends Controller
{
    

    public function get(){        

        if($rooms = Auth::user()->rooms()->get()){
            return $rooms;
        }else{
            return response()->json(['message' => 'Rooms not found.'], 404);
        }
    }

    public function join(Request $request){
        try{
            $this->validate($request, [
                'slug' => 'required|alpha_dash|max:15',
                'token' => 'required|max:25|min:25'
            ]);
        }
        catch( \Illuminate\Validation\ValidationException $e ){
            return $e->getResponse();
        } 


        $inviting_user_id = substr($request->token, -1);
        $token = substr($request->token, 0, 24);

        if($room = Room::where('slug',$request->slug)->where('token',$token)->first()){

            //make sure the inviting user is a member of the room
            if($inviting_user = RoomUser::where('room_id',$room->id)->where('user_id',$inviting_user_id)->first()){

                //finally, make sure this record doesn't already exist

                if($room_user = RoomUser::where('room_id', $room->id)->where('user_id', Auth::user()->id)->first()){
                    return response()->json(['warning' => 'User already belongs to this room.'], 503);
                }else{
                    $room_user = new RoomUser();
                    $room_user->room_id = $room->id;
                    $room_user->user_id = Auth::user()->id;
                    $room_user->inviting_user_id = $inviting_user_id;
                    $room_user->save();

                    return response()->json(['sucess' => 'Welcome.'], 200);
                }
                
            }else{
                //this person's invite code isn't active because they're not a part of that group
                return response()->json(['message' => 'This link is no longer active.'], 404);
            }            

        }else{
            return response()->json(['message' => 'Room not found, or token invalid.'], 404);
        }

    }

    public function find($slug){
        if($room = Room::where('slug',$slug)->first()){
            return $room;
        }else{
            return response()->json(['message' => 'Room not found.'], 404);
        }
    }

    public function create(Request $request)
    {
        try{
            $this->validate($request, [
                'name' => 'required|max:50',
                'slug' => [
                    'required',
                    'alpha_dash',
                    'max:15',
                    'unique:rooms',
                    Rule::notIn(['about', 'me', 'mission', 'patrick', 'contact', 'roadmap', 'faq', 'room', 'dance', 'mingle', 'cowork', 'work', 'codance', 'comingle']),
                ],
                'privacy' => 'required'
            ]);
        }
        catch( \Illuminate\Validation\ValidationException $e ){
            return $e->getResponse();
        } 

        $room = new Room;
        $room->name = $request->name;
        $room->slug = strtolower($request->slug);
        $room->privacy = $request->privacy;
        $room->token = str_random(24);

        $room->text = 'Use this section to describe your room and/or to provide quick links to other tools you use, e.g. your team’s Slack, Zoom, etc.';

        $room->save();

        $room_user = new RoomUser();
        $room_user->room_id = $room->id;
        $room_user->user_id = Auth::user()->id;
        $room_user->role = 'owner';
        $room_user->save();

        return $room;
    }


    
}