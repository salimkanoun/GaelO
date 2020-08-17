<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::post('user', 'UserController@createUser');
Route::get('user/{id?}', 'UserController@getUser');
Route::patch('user', 'UserController@changeUserPassword');
Route::post('login', 'UserController@login');
Route::post('register', 'RegisterController@register');
Route::get('testClean', 'UserController@loginClean');