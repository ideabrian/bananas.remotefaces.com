<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use  App\User;
use  App\File;
use  App\Session;
use Illuminate\Support\Facades\Auth;

class SessionController extends Controller
{

    public function start(Request $request){

        try{
            $this->validate($request, [
                'file' => 'required',
                'room_id' => 'required|exists:rooms,id'
            ]);
        }
        catch( \Illuminate\Validation\ValidationException $e ){
            return $e->getResponse();
        }

        //stop any existing sessions run by this user
        $this->killAllUserSessions();

        $file_id = $this->createSessionFile($request->file);

        //join new session
        $session = new Session();
        $session->room_id = $request->room_id; //TODO this needs to be authenticated
        $session->user_id = Auth::user()->id;
        $session->start_time = Date('Y-m-d h:i:s');
        $session->end_time = Date('Y-m-d h:i:s', time() + 66);
        $session->file_id = $file_id;
        $session->save();

    }

    public function update(Request $request){

        try{
            $this->validate($request, [
                'file' => 'required|sometimes',
                'session_id' => 'required'
            ]);
        }
        catch( \Illuminate\Validation\ValidationException $e ){
            return $e->getResponse();
        } 

        if($session = Session::find($request->session_id)){

            if($request->has('file')){
                $file_id = $this->createSessionFile($request->file);
            }

            $session->end_time = Date('Y-m-d h:i:s', time() + 66);
            $session->save();
        }
    }
    
    public function end(){
        $this->killAllUserSessions();
    }

    private function killAllUserSessions(){
        //get all sessions greater than now, for this user. Kill them all.
        //TODO
        // if($session = Session::where('end_time','>','now()')->first()){
        //     echo $session->id;
        // }
    }

    private function createSessionFile($session_id, $file){

        $client = new S3Client([
            'credentials' => [
                'key'    => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY')
            ],
            'region' => env('AWS_DEFAULT_REGION'),
            'version' => 'latest',
        ]);
        
        $adapter = new AwsS3Adapter($client, env('AWS_BUCKET'));
        $filesystem = new Filesystem($adapter);

        $user = Auth::user();
        $filename = $user->id.'-'.Date('YmdHis').'.jpg';

        $base64_image = $request->input('photo');
        @list($type, $file_data) = explode(';', $base64_image);
        @list(, $file_data) = explode(',', $file_data); 
        Storage::disk('s3')->put($filename, base64_decode($file_data),'public');
        
        $file = new File();
        $file->amazon_url = 'https://remotefaces.s3.amazonaws.com/'.$filename;
        $file->type = 'image';
        $file->save();        

        $user->image_url = $filename;
        $user->save();

        $users = User::whereNotNull('image_url')->orderBy('updated_at','desc')->get();
        return $users;
    
    }


    
}