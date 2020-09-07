<?php
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

//Users Routes
Route::get('users/{id?}', 'UserController@getUser');
Route::post('users', 'UserController@createUser');
Route::put('users/{id}/password', 'UserController@changeUserPassword');
Route::put('users/{id}', 'UserController@modifyUser');
Route::middleware('auth:api')->delete('users/{id}', 'UserController@deleteUser');

//Centers Routes
Route::get('centers/{code?}', 'CenterController@getCenter');
Route::post('centers', 'CenterController@createCenter');

//Mail Route
Route::post('request', 'RequestController@sendRequest');
Route::get('mail', 'UserController@testMail');

//Login Routes
Route::post('login', 'AuthController@login');
Route::middleware('auth:api')->delete('login', 'AuthController@logout');

//test auth middelware
Route::middleware(['auth:api', 'admin'])->get('admin', 'AuthController@logout');

//Miscellaneous Routes
Route::get('countries/{code?}', 'CountryController@getCountry');

//Tools Routes
Route::post('tools/reset-password', 'ToolsController@resetPassword');
