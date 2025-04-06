<?php

namespace App\Http\Controllers\View;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Stream;
use App\Models\Region;
use App\Models\Zone;
use App\Models\Bacenta;

class UpdateUserInfoControllerphp extends Controller
{
    //
    public function index()
    {
        // Get all users
        $users = \App\Models\User::all();
        return view('updateuser', compact('users'));
    }

    public function getUser(Request $request) {

        $user = \App\Models\User::find($request->id);
        if (!$user) {
            return redirect()->route('update_user_church_info')->with('error', 'User not found');
        }

        $user->with(['stream', 'region', 'zone', 'bacenta']);
        $streams = Stream::all();
        $regions = Region::all();
        $zones = Zone::all();
        $bacentas = Bacenta::all();

        return view('showUserWithForm', [
            'user' => $user,
            'streams' => $streams,
            'regions' => $regions,
            'zones' => $zones,
            'bacentas' => $bacentas
            ]
        );
    }

    public function updateUser(Request $request) {
        $user = \App\Models\User::find($request->id);

        dd($request->all());

        // $user->update([
        //     'bacenta_id' => $request->bacenta_id,
        //     'zone_id' => $request->zone_id,
        //     'region_id' => $request->region_id,
        //     'stream_id' => $request->stream_id,
        // ]);

        return redirect()->route('update_user_church_info')->with('success', 'User updated successfully');
    }

}
