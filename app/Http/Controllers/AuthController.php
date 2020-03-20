<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use  App\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    /**
     * Store a new user.
     *
     * @param  Request  $request
     * @return Response
     */
    public function register(Request $request)
    {
        //validate incoming request 
        $this->validate($request, [
            'email' => 'required|email|unique:users',
            'name' => 'required',
            'password' => 'required'
        ]);

        try {           
            $user = new User();
            $user->email = $request->email;
            $user->name = $request->name;
            $user->password = app('hash')->make($request->password);
            $user->token = str_random(16);
            $user->save();

            Mail::send([], [], function ($message) use($user) {
                $message->to($user->email)
                    ->subject('Please confirm your account.')
                    ->setBody('
                    <p>Hi!</p>
                    <p>If you just signed up for a ForHumanSake.org account, please click the link to below to verify your email address. And if you didn’t just sign up... then quite possibly you have made an internet enemy who is now trying to spam you. 🤷‍♀️</p>
                    <p>'. url('/user/verify/'.$user->id.'/'.$user->token) .'</p>'
                    , 'text/html');
            });
            return response()->json([
                'success' => 'Email Sent!'
            ], 200);

            //return successful response
            return response()->json(['user' => $user, 'message' => 'CREATED'], 201);

        } catch (\Exception $e) {
            //return error message
            return response()->json(['message' => $e->getMessage()], 409);
        }

    }

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
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only(['email', 'password']);

        if (! $token = Auth::attempt($credentials, true)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    
}