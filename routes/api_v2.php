<?php

use App\Http\Controllers\API\V2\MemberController;
use App\Http\Controllers\API\V2\AuthController;
use App\Http\Controllers\API\V2\StreamController;
use App\Http\Controllers\API\V2\RegionController;
use App\Http\Controllers\API\V2\BacentaController;
use App\Http\Controllers\API\V2\BasontaController;
use App\Http\Controllers\API\V2\UserController;
use App\Http\Controllers\API\V2\DashboardController;
use App\Http\Controllers\API\V2\ServiceController;
use App\Http\Controllers\API\V2\ZoneController;
use App\Http\Controllers\API\V2\CampaignController;
use App\Http\Controllers\API\V2\AntibrutishController;
use App\Http\Controllers\API\V2\SheepSheekingController;
use App\Http\Controllers\API\V2\MultiplicationCampaignController;
use App\Http\Controllers\API\V2\ShepherdorialCycleController;

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
Route::post('auth/complete-profile', [AuthController::class, 'completeProfile'])->middleware(['auth:sanctum']);

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

        Route::controller(ShepherdorialCycleController::class)->group(function(){
            Route::get('shepherdorial-cycles', 'index');
            Route::get('shepherdorial-cycles/{id}', 'show');
            Route::post('shepherdorial-cycles', 'create');
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

    Route::middleware('role:Super Admin|Bishop|Region Lead|Bacenta Leader')->group(function () {
        Route::controller(BacentaController::class)->group(function(){
            Route::get('bacentas', 'index');
            Route::put('bacentas/{bacenta}', 'update');
            Route::get('bacentas/{bacenta}', 'show');
            Route::post('bacentas', 'create');
            Route::delete('bacentas/{bacenta}', 'destroy');

        });

         Route::controller(ServiceController::class)->group(function(){
            Route::get('services', 'index');
            Route::get('services/types', 'getTypes');
            Route::post('services', 'create');
        });

        Route::controller(BasontaController::class)->group(function(){
            Route::get('basontas', 'index');
        });

        Route::controller(CampaignController::class)->group(function(){
            Route::get('campaigns', 'index');
        });

        Route::controller(AntibrutishController::class)->group(function(){
            Route::post('antibrutish', 'store');
            Route::get('antibrutish/leaders', 'leaderSummary');
            Route::get('antibrutish/total-hours', 'totalHours');
        });

        Route::controller(SheepSheekingController::class)->group(function(){
            Route::post('sheep-seeking', 'store');
            Route::get('sheep-seeking', 'index');
            Route::get('sheep-seeking/total-visits', 'totalVisits');
        });

        Route::controller(MultiplicationCampaignController::class)->group(function(){
            Route::post('multiplication-campaigns', 'store');
            Route::get('multiplication-campaigns', 'index');
            Route::get('multiplication-campaigns/total-souls', 'totalSouls');
        });

    });


});
