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
$router->get('/getWorkers', 'UserController@getWorkers'); 
$router->post('/newsletter/subscribe', 'NewsletterController@createSubscription'); 
$router->get('/newsletter/verify/{id}/{token}', 'NewsletterController@verifySubscription'); 

//$router->post('/pay', 'UserController@pay');

//protected routes
$router->group(['middleware' => 'auth'], function () use ($router) {
    $router->get('/me', 'UserController@profile');
    $router->post('/logout', 'UserController@logout');
    $router->post('/updateImage', 'UserController@updateImageUrl');    
});