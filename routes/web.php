<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

//version info
$router->get('/', function () use ($router) {
    return $router->app->version();
});


//unprotected routes
$router->post('/register', 'AuthController@register');
$router->post('/login', 'AuthController@login');
$router->get('/posts', 'PostController@find');
$router->post('/pay', 'UserController@pay');
$router->get('/user/verify/{id}/{token}', 'UserController@verifyUser'); 

$router->post('/talks', 'TalkController@start'); //start a new conversation.
$router->post('/startEmailConvo', 'TalkController@startEmailConvo'); //start a new eMAIL conversation.
$router->delete('/talks/{daily_room_name}/destroy', 'TalkController@destroy');
$router->post('/talks/{daily_room_name}/initialize', 'TalkController@initialize');
$router->get('/talks', 'TalkController@getAll'); 


$router->post('/newsletter/subscribe', 'NewsletterController@createSubscription'); 
$router->get('/newsletter/verify/{id}/{token}', 'NewsletterController@verifySubscription'); 

$router->get('/listener', 'UserController@getFirstListener'); 

//protected routes
$router->group(['middleware' => 'auth'], function () use ($router) {
    $router->get('/me', 'UserController@profile');    
    $router->post('/logout', 'UserController@logout');   

    //Create a new forum post
    $router->post('/posts', 'PostController@create');

    $router->post('/users/{id}/updateAvailability', 'UserController@updateAvailability');

    $router->get('/talks/getLatestUnanswered', 'TalkController@getLatestUnanswered'); //protect is isListener middleware
    $router->post('/talks/{daily_room_name}/claim', 'TalkController@claimCall'); //protect is isListener middleware

});