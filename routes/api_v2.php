<?php

use App\Http\Controllers\API\V2\MemberController;
use App\Http\Controllers\API\V2\AuthController;
use App\Http\Controllers\API\V2\StreamController;
use App\Http\Controllers\API\V2\RegionController;
use App\Http\Controllers\API\V2\BacentaController;
use App\Http\Controllers\API\V2\UserController;
use App\Http\Controllers\API\V2\DashboardController;
use App\Http\Controllers\API\V2\ServiceController;
use App\Http\Controllers\API\V2\ZoneController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v2 Routes
|--------------------------------------------------------------------------
|
| Everything in this file is mounted under the /api/v2 prefix from
| routes/api.php.
|
| Recommended structure (optional):
| - Create versioned controllers under App\Http\Controllers\API\V2\...
| - Move/duplicate endpoints here as you iterate on the v2 contract
|
*/

Route::get('/health', function (Request $request) {
    return 'ok';
});

Route::post('auth/google', [AuthController::class, 'googleAuth']);

// TODO: Add v2-only endpoints here.k
Route::middleware(['auth:sanctum', 'setDatabase'])->group(function () {

    Route::controller(MemberController::class)->group(function () {
        Route::get('members', 'index');
        Route::post('members', 'create');
    });

    Route::middleware('role:Super Admin|Bishop')->group(function () {
        Route::controller(StreamController::class)->group(function(){
            Route::get('streams', 'index');
            Route::get('streams/{stream}', 'show');
        });

        // Route::controller(UserController::class)->group(function(){
        //     Route::get('/users', 'index');
        //     Route::post('/users', 'create');
        //     Route::get('/roles', 'getRoles');
        //     Route::post('/roles', 'createRole');
        // });
    });

    Route::middleware('role:Super Admin|Bishop|Stream Lead|Region Lead')->group(function () {
        Route::controller(RegionController::class)->group(function(){
        Route::get('regions', 'index');
        Route::get('regions/{id}', 'show');
        Route::post('regions', 'create');
        Route::put('regions/{id}', 'update');
        Route::delete('regions/{id}', 'destroy');

        Route::get('regions/{id}/bacentas', 'getBacentas');
        Route::get('regions/{id}/members', 'getMembers');
        Route::get('regions/{id}/services', 'getServices');
        });
    });

    Route::middleware('role:Super Admin|Bishop')->group(function () {
        Route::controller(UserController::class)->group(function(){
            Route::get('leaders', 'index');
            Route::get('/roles', 'getRoles');
        });
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::controller(DashboardController::class)->group(function(){
        Route::get('dashboard', 'index');
        });
    });

    Route::middleware('role:Super Admin|Bishop|Region Lead|Zone Lead')->group(function () {
        Route::controller(ZoneController::class)->group(function(){
            Route::get('zones', 'index');
            Route::get('zones/{zone}', 'show');
            Route::post('zones', 'create');
            Route::put('zones/{zone}', 'update');
            Route::delete('zones/{zone}', 'destroy');

        });
    });

    Route::middleware('role:Super Admin|Bishop|Region Lead|Bacenta Lead')->group(function () {
        Route::controller(BacentaController::class)->group(function(){
            Route::get('bacentas', 'index');
            Route::put('bacentas/{bacenta}', 'update');
            Route::get('bacentas/{bacenta}', 'show');
            Route::post('bacentas', 'create');
            Route::delete('bacentas/{bacenta}', 'destroy');

        });
    });

    Route::middleware('role:Super Admin|Bishop|Stream Lead|Region Lead|Bacenta Lead')->group(function () {
        Route::controller(ServiceController::class)->group(function(){
            Route::get('services', 'index');
            Route::get('services/types', 'getTypes');
            Route::post('services', 'create');
        });
    });

    


});
