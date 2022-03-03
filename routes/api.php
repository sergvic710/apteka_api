<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

//use App\Http\Controllers\AuthController;

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

//dd($_SERVER['REQUEST_URI']);
Route::middleware('api')
    ->post('/sso/connect/token','AuthController@token');

//Route::middleware(['auth:api','prefix' => 'v5'])
//    ->post('/users/create','UsersController@create1');

Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'v5'
], function ($router) {
    Route::post('/users/create','UsersController@create');
});

Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'v5/auth'
], function ($router) {
    Route::post('login', 'AuthController@login');
    Route::post('logout', 'AuthController@logout');
    Route::post('refresh', 'AuthController@refresh');
    Route::post('me', 'AuthController@me');
});
Route::group([
    'middleware' => 'api',
    'prefix' => 'v5/auth'
], function ($router) {
    Route::post('login', 'AuthController@login');
});

Route::group([
    'middleware' => ['auth:api','request.logging'],
//    'middleware' => ,
    'prefix' => 'v5/stores'
], function() {
    Route::get('{storeId}/stocks','StoresController@getStock'); //5. Получить от АСНА все имеюшиеся у нее остатки по аптеке
    Route::post('{storeId}/stocks','StoresController@stock'); //8. Запрос на передачу остатков аптеки в АСНА
    Route::put('{storeId}/stocks','StoresController@stockParth'); //8. Запрос на передачу частичных остатков
    Route::delete('{storeId}/stocks','StoresController@deleteAll');  //2.  Удалить в АСНА все имеющиеся остатки по этой аптеке
    Route::get('{storeId}/orders_exchanger','StoresController@getOrders'); //4. Запрос на получение состояния заказов
    Route::put('{storeId}/orders_exchanger','StoresController@setStatusOrders'); //6. Запрос на получение состояния заказов
});

Route::group([
    'middleware' => ['auth:api','request.logging'],
    'prefix' => 'v5/references'
], function() {
    Route::get('goods_links','ReferencesController@goodLinks');  //3. Получить связи товаров АСНА с нашими товарами
});

Route::group([
    'middleware' => ['auth:api','request.logging'],
    'prefix' => 'v5/legal_entities'
], function() {
    Route::post('{storeId}/preorders','EntitiesController@preorders');  //7. Отправить прайс лист
});


//Route::get('/login', 'AuthController@login')->name('login');


//Route::middleware('auth:api')->get('/user', function (Request $request) {
//    return $request->user();
//});
//Route::apiResource('/users', 'Users');
