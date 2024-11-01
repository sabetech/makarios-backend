<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\RegisterController;
use App\Http\Controllers\API\ChurchController;
use App\Http\Controllers\API\StreamController;
use App\Http\Controllers\API\MemberController;
use App\Http\Controllers\API\CouncilController;
use App\Http\Controllers\API\BacentaController;
use App\Http\Controllers\API\BasontaController;
use App\Http\Controllers\API\UserController;

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

    Route::controller(StreamController::class)->group(function(){
        Route::get('streams', 'index');
        Route::get('stream/{stream}', 'show');
    });

    Route::controller(CouncilController::class)->group(function(){
        Route::get('councils', 'index');
        Route::get('council/{council}', 'show');
    });

    Route::controller(BacentaController::class)->group(function () {
        Route::get('bacentas', 'index');
        Route::get('bacenta/{bacenta}', 'show');
    });

    Route::controller(BasontaController::class)->group(function () {
        Route::get('basontas', 'index');
    });

    Route::post('logout', [RegisterController::class, 'logout']);

});

Route::get('/health', function(Request $request){
    return 'ok';
});
