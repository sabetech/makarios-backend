<?php

use App\Http\Controllers\API\V2\MemberController;
use App\Http\Controllers\API\V2\AuthController;
use App\Http\Controllers\API\V2\StreamController;
use App\Http\Controllers\API\V2\RegionController;
use App\Http\Controllers\API\V2\BacentaController;
use App\Http\Controllers\API\V2\UserController;
use App\Http\Controllers\API\V2\DashboardController;
use App\Http\Controllers\API\V2\ServiceController;
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
        Route::get('stream/{stream}', 'show');
        });
    });

    Route::middleware('role:Super Admin|Bishop|Stream Lead|Region Lead')->group(function () {
        Route::controller(RegionController::class)->group(function(){
        Route::get('regions', 'index');
        });
    });

    Route::middleware('role:Super Admin|Bishop')->group(function () {
        Route::controller(UserController::class)->group(function(){
        Route::get('leaders', 'index');
        });
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::controller(DashboardController::class)->group(function(){
        Route::get('dashboard', 'index');
        });
    });

    Route::middleware('role:Super Admin|Bishop|Region Lead|Bacenta Lead')->group(function () {
        Route::controller(BacentaController::class)->group(function(){
        Route::get('bacentas', 'index');
        });
    });

    Route::middleware('role:Super Admin|Bishop|Stream Lead|Region Lead|Bacenta Lead')->group(function () {
        Route::controller(ServiceController::class)->group(function(){
        Route::get('services', 'index');
        });
    });

    


});
