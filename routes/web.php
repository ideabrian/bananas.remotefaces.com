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
$router->get('/user/verify/{id}/{token}', 'UserController@verifyUser'); 
$router->get('/getWorkers/1', 'UserController@getWorkers');
$router->post('/newsletter/subscribe', 'NewsletterController@createSubscription'); 
$router->get('/newsletter/verify/{id}/{token}', 'NewsletterController@verifySubscription'); 

//$router->post('/pay', 'UserController@pay');
$router->get('/rooms/{slug}', 'RoomController@find');

//protected routes
$router->group(['middleware' => 'auth'], function () use ($router) {
    $router->get('/me', 'UserController@profile');
    $router->post('/logout', 'UserController@logout');
    $router->post('/updateImage', 'UserController@updateImageUrl');

    $router->post('/rooms', 'RoomController@create');
    $router->post('/rooms/join', 'RoomController@join');
    $router->get('/rooms', 'RoomController@get');

    $router->post('/sessions/start', 'SessionController@start');
    $router->post('/sessions/update', 'SessionController@update');
    $router->post('/sessions/end', 'SessionController@end');

    $router->get('/getWorkers/{room_id}', 'UserController@getWorkers');

});