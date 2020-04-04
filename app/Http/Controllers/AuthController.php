<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use  App\User;
use  App\RoomUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{    
     /**
     * Get a JWT via given credentials.
     *
     * @param  Request  $request
     * @return Response
     */
    public function login(Request $request)
    {
          //validate incoming request 
        $this->validate($request, [
            'id' => 'required|exists:users,id',
            'token' => 'required|string',
        ]);

        if($user = User::where('id',$request->id)->where('token',$request->token)->first()){
            if (! $token = Auth::tokenById($user->id, true)) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }
            $user->token = null;
            $user->save();            
            return $this->respondWithToken($token);
        }else{
            return response()->json(['message' => 'Link expired.'], 401);
        }
                
    }

    
}