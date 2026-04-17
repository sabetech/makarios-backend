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
use App\Http\Controllers\API\MicrochurchController;
use App\Http\Controllers\API\CampaignController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

// API v2 routes live under /api/v2/*
Route::prefix('v2')->group(base_path('routes/api_v2.php'));

Route::middleware('setDatabase')->group(function () {
    
    Route::controller(RegisterController::class)->group(function(){
        Route::post('register', 'register');
        Route::post('login', 'login');
        Route::post('users/upload-photo', 'uploadPhoto');
    });

});
Route::middleware(['auth:sanctum', 'setDatabase'])->group( function () {
    Route::resource('churches', ChurchController::class);

    Route::controller(UserController::class)->group(function(){
        Route::get('/user', 'getUserViaEmail');
        Route::get('/dashboard-summary', 'getDashboardSummary');
        Route::get('/users', 'index');
        Route::post('/users', 'create');
        Route::get('/roles', 'getRoles');
        Route::post('/roles', 'createRole');
    });

    Route::controller(MemberController::class)->group(function(){
        Route::get('members', 'index');
        Route::get('members/{id}', 'show');
        Route::post('members', 'create');
        Route::put('members/{id}', 'update');
        Route::delete('members/{id}', 'destroy');

    });

    Route::controller(RegionController::class)->group(function(){
        Route::get('regions', 'index');
        Route::get('regions/{id}', 'show');
    });

    Route::controller(StreamController::class)->group(function(){
        Route::get('streams', 'index');
        Route::get('stream/{stream}', 'show');
        Route::get('streams/regions', 'getRegionsViaStreams');
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
        Route::get('service/newIndex', 'newIndex');
        Route::get('service/averages', 'calculateServiceAverages');
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
