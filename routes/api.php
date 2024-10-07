<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\RegisterController;
use App\Http\Controllers\API\ChurchController;



Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::controller(RegisterController::class)->group(function(){
    Route::post('register', 'register');
    Route::post('login', 'login');
    Route::post('users/upload-photo', 'uploadPhoto');
});

Route::middleware('auth:sanctum')->group( function () {
    Route::resource('churches', ChurchController::class);

    Route::post('logout', [RegisterController::class, 'logout']);

});

Route::get('/health', function(Request $request){
    return 'ok';
});
