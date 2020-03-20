<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use  App\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

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

    public function verifyUser($id, $token){
        if($user = User::where('id', $id)->where('token',$token)->where('is_confirmed',0)->first()){
            $user->is_confirmed = true;
            $user->save();

            //TODO send welcome email.

            echo 'User confirmed. Muchas gracias! <a href="'.env('FRONTEND_URL').'">Return to site.</a>';

        }else{
            echo 'Error.';
        }
    }

    public function updateImageUrl(Request $request){        

        $user = Auth::user();
        $filename = $user->id.'-'.Date('YmdHis').'.gif';

        $base64_image = $request->input('photo');
        @list($type, $file_data) = explode(';', $base64_image);
        @list(, $file_data) = explode(',', $file_data); 
        Storage::disk('local')->put($filename, base64_decode($file_data));
        
        $user->image_url = $filename;
        $user->save();

        return response()->json(['image_url' => $filename], 200);
    }

    /**
     * Get the authenticated User.
     *
     * @return Response
     */
    public function profile()
    {
        return response()->json(Auth::user(), 200);
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