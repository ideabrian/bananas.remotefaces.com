<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use  App\User;
use  App\File;
use  App\Session;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

use Aws\S3\S3Client;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;

use Illuminate\Database\Eloquent\Builder;
use App\Helper;

class UserController extends Controller
{

     /**
     * Instantiate a new UserController instance.
     *
     * @return void
     */
    public function __construct()
    {
        
    }

    public function getWorkers($room_id = 1){

        //TODO only return something here if this group is public or if Auth::user() belongs to this group.

        $users = User::whereHas('rooms', function (Builder $query) use($room_id) {
            $query->where('room_id', $room_id);
        })->with('file')->whereNotNull('file_id')->orderBy('updated_at','desc')->get();
        return $users;
    }
    
    public function sendLoginLink(Request $request){
        try{
            $this->validate($request, [
                'email' => 'required|exists:users,email',
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

        if($user = User::where('email', $request->email)->first()){
            //now make sure the user is in the room
            if($room = Room::where('user_id', $user->id)->where('id', $request->room_id)->first()){
                Helper::sendLoginLink($user->id, $request->room_id);
                return response()->json(['success' => true], 200);
            }
        }

        return response()->json(['error' => true, 'message' => 'This email address isn’t a member of this room.'], 404);

        
    }

    public function setEmail(Request $request){
        try{
            $this->validate($request, [
                'email' => 'required|email',
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
                return response()->json(['success' => true, 'user' => $user], 200);
            }
        }else{
            $user = new User();
            $user->email = $request->email;
            $user->save();            
        }

        return response()->json(['success' => true, 'user_id' => $user->id], 200);
        

    }

    public function setUsername(Request $request){        

        try{
            $this->validate($request, [
                'user_id' => 'required|exists:users,id',
                'username' => [
                    'required',
                    'alpha_dash',
                    'max:15',
                    'unique:users',
                    Rule::notIn(['terms','privacy','help','contact', 'new', 'join', 'login']),
                ],
                'room_id' => 'required|sometimes',
                'name' => 'required|integer'
            ]);
        }catch( \Illuminate\Validation\ValidationException $e ){
            return $e->getResponse();
        }  

        if($request->name != 742){
            return response()->json(['message' => 'Not today, junior.'], 403);
        }

        if($user = User::find($request->user_id)){
            if($user->username){
                //the user already has a username, so we can't override it.
                return response()->json(['error' => true, 'message' => 'Not allowed.'], 501);
            }else{
                $user->username = $request->username;
                $user->save();

                if($request->has('room_id')){
                    Helper::sendLoginLink($request->user_id, $request->room_id);
                }

                return response()->json(['success' => true], 200);    
            }

        }else{
            return response()->json(['error' => true, 'message' => 'User not found.'], 404);
        }

    }

    public function updateImageUrl(Request $request){        

        try{
            $this->validate($request, [
                'room_id' => 'required|exists:rooms,id',
                'still' => 'required'
            ]);
        }catch( \Illuminate\Validation\ValidationException $e ){
            return $e->getResponse();
        }         


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

        //still image
        $filename = $user->id.'-'.Date('YmdHis').'.jpg';
        $base64_image = $request->input('still');
        @list($type, $file_data) = explode(';', $base64_image);
        @list(, $file_data) = explode(',', $file_data); 
        Storage::disk('s3')->put($filename, base64_decode($file_data),'public');
        
        $file = new File();
        $file->user_id = $user->id;
        $file->amazon_url = 'https://remotefaces.s3.amazonaws.com/'.$filename;
        $file->type = 'image';
        $file->save();

        $user->file_id = $file->id;

        //gif
        if($request->has('gif') && $request->gif != NULL){
            $filename = $user->id.'-'.Date('YmdHis').'.gif';
            $base64_image = $request->input('gif');
            @list($type, $file_data) = explode(';', $base64_image);
            @list(, $file_data) = explode(',', $file_data); 
            Storage::disk('s3')->put($filename, base64_decode($file_data),'public');
            
            $file = new File();
            $file->user_id = $user->id;
            $file->amazon_url = 'https://remotefaces.s3.amazonaws.com/'.$filename;
            $file->type = 'gif';
            $file->save();

            $user->file_id = $file->id; 
        }

        $user->save(); //only sets last_updated time

        return $this->getWorkers($request->room_id);

    }

    
    public function profile()
    {
        return response()->json(Auth::user(), 200);
    }   
    
    //get profile along with 
    public function profileFromRoom(Request $request)
    {
        if($room_id = Helper::getRoomIDFromRequest()){
            $user = Auth::user()->load(['deets' => function ($query) use($room_id){
                $query->where('room_id', $room_id);
            }]);
            return response()->json($user, 200);
        }else{
            return response()->json(['message' => 'Room not found.'], 404);
        }


        
    } 

    public function logout()
    {
        Auth::logout();
        return response()->json(['message' => 'Successfully logged out.'], 200);
    }


    public function pay(Request $request){

        //validation
        try{
            $this->validate($request, [
                'name' => 'required',
                'number' => 'required',
                'expiration' => 'required',
                'security' => 'required',
                'user_id' => 'required|exists:users,id'
            ]);
        }catch( \Illuminate\Validation\ValidationException $e ){
            return $e->getResponse();
        } 

        try{
            \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));

            $input = $request->all();
            
            $expiration = explode("/", $request->get('expiration'));

            $token = \Stripe\Token::create(
                array(
                    "card" => array(
                        "name" => $input['name'],
                        "number" => $input['number'],
                        "exp_month" => trim($expiration[0]),
                        "exp_year" => trim($expiration[1]),
                        "cvc" => $input['security']
                    )
                )
            );            
            
            $user = User::find($request->get('user_id')); 

            //make the charge
            if($stripe_customer = \Stripe\Customer::create(array("email" => $user->email, "source" => $token))){       
                $description = 'ForHumanSake.org Early Bird';
                $price = 10;
                if(\Stripe\Charge::create(array(
                    "amount" => ($price * 100),
                    "currency" => "usd",
                    "customer" => $stripe_customer->id,                     
                    "description" => $description
                ))){
                   
                    $user->name = $request->get('name');
                    $user->stripe_id = $stripe_customer->id;
                    $user->stripe_last_four = $stripe_customer->sources->data[0]->last4;
                    $user->stripe_brand = $stripe_customer->sources->data[0]->brand;
                    $user->save();

                    Mail::send([], [], function ($message) use($user) {
                        $message->to($user->email)
                            ->subject('Thanks for Investing in Mental Health')
                            ->cc('patrick@forhumansake.org')
                            ->setBody('
                            <p>TODO</p>
                            <p>Cheers,<br/>Patrick</p>'
                            , 'text/html');
                    });
                    
                    return response()->json([
                        'success' => 'Payment processed!'
                    ], 200);
                }
            }              

            return response()->json(['error' => 'Something went wrong. Please email patrick@lorenzut.com and we’ll get everything squared away.'], 503);

            
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 503);            
        } 
    }

}