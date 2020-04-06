<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use  App\User;
use  App\Room;
use  App\RoomUser;
use  App\File;
use  App\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Helper;

class RoomController extends Controller
{

    private function createRoomUser($user_id, $room_id){

        if($room_user = RoomUser::where('room_id',$room_id)->where('user_id',$user_id)->first()){
            //no need to create this new relationship. User already belongs to room.
        }else{
            $room_user = new RoomUser();
            $room_user->user_id = $user_id;
            $room_user->room_id = $room_id;
            $room_user->save();  
        }
          
        return $room_user;
    }

    public function subscribe(Request $request){
        try{
            $this->validate($request, [
                'email' => 'required|email',
                'room_id' => 'required|exists:rooms,id',
                'name' => 'required|integer'
            ]);
        }
        catch( \Illuminate\Validation\ValidationException $e ){
            return $e->getResponse();
        } 

        if($request->name != 742){
            return response()->json(['message' => 'Not today, junior.'], 403);
        }

        if($user = User::where('email',$request->email)->first()){                                                
            //do this check, because if the user doesn't yet have a username then we can't skip this step on the frontend
            if($user->username){
                $room_user = $this->createRoomUser($user->id, $request->room_id);
                Helper::sendLoginLink($user->id, $request->room_id);
                return response()->json(['success' => true, 'user' => $user], 200);
            }
        }else{
            $user = new User();
            $user->email = $request->email;
            $user->save();            
        }

        $room_user = $this->createRoomUser($user->id, $request->room_id);
        return response()->json(['success' => true, 'user_id' => $user->id], 200);        

    }
    
    public function update(Request $request, $id){
        try{
            $this->validate($request, [
                'name' => 'required',
                'title' => 'required',
                'subtitle' => 'required',
                'pitch' => 'required'                
            ]);
        }
        catch( \Illuminate\Validation\ValidationException $e ){
            return $e->getResponse();
        } 

        if($room = Room::with('users')->find($id)){
            
            //todo make sure this user is admin
            
            $room->name = $request->name;
            $room->title = $request->title;
            $room->subtitle = $request->subtitle;
            $room->pitch = $request->pitch;
            $room->save();

            return $room;
        }else{
            return response()->json(['message' => 'Room not found.'], 404);
        }

    }

    public function find($slug){
        if($room = Room::with('users')->where('slug',$slug)->orWhere('domain',$slug)->first()){
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
                'firstName' => 'required|integer',
                'slug' => [
                    'required',
                    'alpha_dash',
                    'max:15',
                    'unique:rooms',
                    Rule::notIn(['api','forum','help','community','discuss']),
                ],
                'user_id' => 'required|exists:users,id'
            ]);
        }
        catch( \Illuminate\Validation\ValidationException $e ){
            return $e->getResponse();
        } 

        if($request->firstName != 742){
            return response()->json(['message' => 'Not today, junior.'], 403);
        }

        $room = new Room;
        $room->name = $request->name;
        $room->slug = strtolower($request->slug);
        $room->privacy = 'visible';
        $room->token = str_random(24);

        $room->title = 'Cowork with '.$request->name;
        $room->pitch = '';
        $room->subtitle = '';
        $room->notes = 'Use this section to describe your room and/or to provide quick links to other tools you use, e.g. your team’s Slack, Zoom, etc.';

        $room->save();
        
        $room_user = new RoomUser();
        $room_user->room_id = $room->id;
        $room_user->user_id = $request->user_id;
        $room_user->role = 'owner';
        $room_user->save();

        Helper::sendLoginLink($request->user_id, $room->id);

        return response()->json(['success' => true], 200);
    }


    
}