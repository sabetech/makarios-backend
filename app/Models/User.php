<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;


class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'home_address',
        'password',
        'img_url',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function churches() {
        return $this->hasMany(Church::class, 'leader_id', 'id');
    }

    public function stream() {
       return $this->hasOneThrough(Stream::class, UserChurchInfo::class, 'id', 'id', 'user_id', 'stream_id');
    }

    //TODO:: This should get the list of regions for user who is a stream lead: ie Bishops
    public function regions() {
        return $this->hasManyThrough(Region::class, UserChurchInfo::class, 'id', 'id', 'user_id', 'region_id');
    }

    public function region() {

        return $this->hasOne(Region::class, 'leader_id', 'id');
    }

    public function zone() {
        return $this->hasOne(Zone::class, 'leader_id', 'id');
    }

    public function isLeaderOf() {

        if ($this->roles->count() > 0) {
            // if (($this->roles[0]->name) == 'Super Admin' ) {
            //     return $this->churches;
            // }

            if (($this->roles[0]->name) == 'Bishop' ) {
                if ($this->stream) {
                    return $this->stream->name;
                }else{
                    return "Unassigned Stream";
                }
            }

            if (($this->roles[0]->name) == 'Region Lead' ) {
                if ($this->region) {
                    return $this->region->region;
                }else{
                    return "Unassigned Region";
                }
            }
            if (($this->roles[0]->name) == 'Zone Lead' ) {
                if ($this->zone) {
                    return $this->zone->name;
                }else{
                    return "Unassigned Zone";
                }
            }

            if (($this->roles[0]->name) == 'Bacenta Leader' ) {
                if ($this->bacenta) {
                    return $this->bacenta->name;
                }else{
                    return "Unassigned Bacenta";
                }
            }
            return "Unassigned";
        }else{
            return "No Role";
        }
    }

    public function bacenta() {
        return $this->hasOne(Bacenta::class, 'leader_id', 'id');
    }

    public function bacentas() {
        if ($this->roles->count() > 0) {
            if ($this->roles[0]->name == 'Super Admin' || $this->roles[0] == 'Bishop' || $this->roles[0] == 'General Admin') {
                return Bacenta::select();
            }

            if ($this->roles[0]->name == 'Stream Lead') {
                return $this->stream()->bacentas();
            }

            if ($this->roles[0]->name == 'Region Lead') {
                return $this->region()->bacentas();
            }
        }
        return $this->hasMany(Bacenta::class, 'leader_id', 'id');
    }

    // public function members() {
    //     return $this->hasMany(Member::class, 'leader_id', 'id');
    // } TODO:: Come back to this. It looks legit but that's now what I'm working on right now

    public function Microchurches() {
        //if the person is a stream lead, they should see all microchurches in their stream
        if ($this->roles[0]->name == 'Stream Lead') {
            return $this->stream->microchurches();
        }

        // if the person is a region lead, they should see all microchurches in their region
        if ($this->roles[0]->name == 'Region Lead') {
            return $this->region->microchurches();
        }

        //if the person is a microchurch leader.
        return $this->hasMany(MicroChurch::class, 'leader_id', 'id');

    }

}
