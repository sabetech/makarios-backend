<?php

namespace App\Http\Controllers\View;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Stream;
use App\Models\Region;
use App\Models\Zone;
use App\Models\Bacenta;
use App\Models\UserChurchInfo;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

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
        $regions = Region::with('stream')->get();
        $zones = Zone::all();
        $bacentas = Bacenta::with('region')->get();

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

        if ($user == null) {
            return redirect()->route('getUserInfo')->with('error', 'User not found');
        }

        if ($request->hasFile('profile_picture')) {
            $file = $request->file('profile_picture');
            $result = Cloudinary::upload($file->getRealPath(), [
                'folder' => 'app-users',
                'public_id' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'overwrite' => false,
                'resource_type' => 'image',
                'transformation' => [
                    'quality' => 'auto:eco',
                    'fetch_format' => 'auto',
                ]
            ]);

            $imageUrl = $result->getSecurePath();
        }

        $user->update(
            [
                'phone' => $request->phone,
                'home_address' => $request->address,
                'img_url' => $imageUrl ?? $user->img_url,
            ]
        );

        if ($request->get('stream') == null) {
            $stream_id = null;

            UserChurchInfo::updateOrCreate(
                [
                    'user_id' => $user->id
                ],
                [
                    'stream_id' => $stream_id,
                    'church_id' => 1, //to be updated later when other churches are on board
                ]
            );

            $userChurchInfo = UserChurchInfo::where('user_id', $user->id)->first();

            return view('confirmUpdate')
                ->with('user', $user)
                ->with('bacenta', $userChurchInfo->bacenta->name ?? "No Bacenta")
                ->with('zone', $userChurchInfo->zone->name ?? "No Zone")
                ->with('region', $userChurchInfo->region->name ?? "No Region")
                ->with('stream', $userChurchInfo->stream->name ?? "No Stream")
                ->with('church', $userChurchInfo->church->name);

        } else {
            $stream = Stream::find($request->get('stream'));

            if ($stream) {
                $stream_id = $stream->id;
            } else {
                $stream = Stream::updateOrCreate([
                    'name' => $request->get('stream'),
                ],
                [
                    'stream_overseer_id' => $user->id,
                ]);

                $stream_id = $stream->id;
            }
        }


        if ($request->get('region') == null) {
            $region_id = null;

            UserChurchInfo::updateOrCreate(
                [
                    'user_id' => $user->id
                ],
                [
                    'region_id' => $region_id,
                    'stream_id' => $stream_id,
                    'church_id' => 1, //to be updated later when other churches are on board
                ]
            );

            $userChurchInfo = UserChurchInfo::where('user_id', $user->id)->first();

            return view('confirmUpdate')
                ->with('user', $user)
                ->with('bacenta', $userChurchInfo->bacenta->name ?? "No Bacenta")
                ->with('zone', $userChurchInfo->zone->name ?? "No Zone")
                ->with('region', $userChurchInfo->region->name ?? "No Region")
                ->with('stream', $userChurchInfo->stream->name ?? "No Stream")
                ->with('church', $userChurchInfo->church->name);
        }else {
            $region = Region::find($request->get('region'));

            if ($region) {
                $region_id = $region->id;
            } else {
                $region = Region::updateOrCreate([
                    'leader_id' => $user->id,
                ],
                [
                    'name' => $request->get('region'),
                    'stream_id' => $stream_id,
                ]);

                $region_id = $region->id;
            }
        }

        if ($request->get('zone') == null) {
           $zone_id = null;
        }else {
            $zone = Zone::find($request->get('zone'));

            if ($zone) {
                $zone_id = $zone->id;
            } else {
                $zone = Zone::create([
                    'leader_id' => $user->id,
                ],
                [
                    'name' => $request->get('zone'),
                    'region_id' => $region_id,
                ]);

                $zone_id = $zone->id;
            }
        }


        if ($request->get('bacenta') == null) {
            $bacenta_id = null;
        } else {
            $bacenta = Bacenta::find($request->get('bacenta'));

            if ($bacenta) {
                $bacenta_id = $bacenta->id;
            } else {
                $bacenta = Bacenta::create([
                    'leader_id' => $user->id,
                ],
                [
                    'name' => $request->get('bacenta'),
                    'zone_id' => $zone_id,
                ]);
                $bacenta_id = $bacenta->id;
            }
        }

        UserChurchInfo::updateOrCreate(
            [
                'user_id' => $user->id
            ],
            [
                'bacenta_id' => $bacenta_id,
                'zone_id' => $zone_id,
                'region_id' => $region_id,
                'stream_id' => $stream_id,
                'church_id' => 1, //to be updated later when other churches are on board
            ]
        );

        $userChurchInfo = UserChurchInfo::where('user_id', $user->id)->first();

        return view('confirmUpdate')
            ->with('user', $user)
            ->with('bacenta', $userChurchInfo->bacenta->name ?? "No Bacenta")
            ->with('zone', $userChurchInfo->zone->name ?? "No Zone")
            ->with('region', $userChurchInfo->region->name ?? "No Region")
            ->with('stream', $userChurchInfo->stream->name ?? "No Stream")
            ->with('church', $userChurchInfo->church->name);
    }
}

