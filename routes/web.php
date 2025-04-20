<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\View\UpdateUserInfoControllerphp;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/update_information', [UpdateUserInfoControllerphp::class, 'index'])->name('update_user_church_info');
Route::post('/update_information', [UpdateUserInfoControllerphp::class, 'getUser'])->name('getUserInfo');
Route::put('/update_user/{id}', [UpdateUserInfoControllerphp::class, 'updateUser'])->name('update_user');
Route::get('/update_user/{id}', [UpdateUserInfoControllerphp::class, 'getUser'])->name('getUser');

