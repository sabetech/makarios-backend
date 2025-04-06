<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\View\UpdateUserInfoControllerphp;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/update_information', [UpdateUserInfoControllerphp::class, 'index'])->name('update_user_church_info');
Route::post('/update_information', [UpdateUserInfoControllerphp::class, 'getUser'])->name('getUser');
Route::put('/update_user', [UpdateUserInfoControllerphp::class, 'updateUser'])->name('updateUser');

