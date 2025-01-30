<?php

use App\Http\Controllers\API\ArrivalController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\RegisterController;
use App\Http\Controllers\API\ChurchController;
use App\Http\Controllers\API\StreamController;
use App\Http\Controllers\API\MemberController;
use App\Http\Controllers\API\ZoneController;
use App\Http\Controllers\API\BacentaController;
use App\Http\Controllers\API\BasontaController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\RegionController;
use App\Http\Controllers\API\ServiceController;
use App\Models\Arrival;
use Illuminate\Support\Arr;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::controller(RegisterController::class)->group(function(){
    Route::post('register', 'register');
    Route::post('login', 'login');
    Route::post('users/upload-photo', 'uploadPhoto');
});

Route::middleware('auth:sanctum')->group( function () {
    Route::resource('churches', ChurchController::class);

    Route::controller(UserController::class)->group(function(){
        Route::get('/user', 'getUserViaEmail');
        Route::get('/dashboard-summary', 'getDashboardSummary');
    });

    Route::controller(MemberController::class)->group(function(){
        Route::get('members', 'index');
        Route::post('members', 'create');
    });

    Route::controller(RegionController::class)->group(function(){
        Route::get('regions', 'index');
        Route::get('regions/{id}', 'show');
    });

    Route::controller(StreamController::class)->group(function(){
        Route::get('streams', 'index');
        Route::get('stream/{stream}', 'show');
    });

    Route::controller(ZoneController::class)->group(function(){
        Route::get('zones', 'index');
        Route::get('zone/{zone}', 'show');
    });

    Route::controller(BacentaController::class)->group(function () {
        Route::get('bacentas', 'index');
        Route::get('bacenta/{bacenta}', 'show');
    });

    Route::controller(BasontaController::class)->group(function () {
        Route::get('basontas', 'index');
    });

    Route::controller(ServiceController::class)->group(function () {
        Route::get('service/types', 'serviceTypes');
        Route::post('service', 'create');
        Route::get('services', 'index');
    });

    Route::controller(ArrivalController::class)->group(function () {
        Route::get('arrivals', 'index');
        Route::post('arrivals', 'create');
    });

    Route::post('logout', [RegisterController::class, 'logout']);

});

Route::get('/health', function(Request $request){
    return 'ok';
});
