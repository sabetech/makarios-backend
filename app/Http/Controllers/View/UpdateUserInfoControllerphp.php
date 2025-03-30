<?php

namespace App\Http\Controllers\View;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UpdateUserInfoControllerphp extends Controller
{
    //
    public function index()
    {
        // Get all users
        $users = \App\Models\User::all();
        return view('updateuser', compact('users'));
    }
}
