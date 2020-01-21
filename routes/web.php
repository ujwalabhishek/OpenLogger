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
$router->get('/openlogger', 'OpenloggerController@index');
$router->post('/openlogger/write', 'OpenloggerController@write');
$router->get('/openlogger/view', 'OpenloggerController@viewlogdir');
$router->post('/openlogger/search', 'OpenloggerController@search');
$router->post('/openlogger/read', 'OpenloggerController@read');
