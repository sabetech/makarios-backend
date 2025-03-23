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

        if ($this->roles[0]->name == 'Stream Lead') {
            return $this->hasOne(Stream::class, 'stream_overseer_id', 'id');
        }

        if ($this->roles[0]->name == 'Stream Admin') {
            return $this->hasOne(Stream::class, 'stream_admin_id', 'id');
        }

        return $this->region->stream();

    }

    public function regions() {

    }

    public function region() {
        if ($this->roles[0]->name == 'Region Lead') {
            return $this->hasOne(Region::class, 'leader_id', 'id');
        }

        if ($this->roles[0]->name == 'Zone Lead') {
            return $this->zone()->region();
        }

        if ($this->roles[0]->name == 'Bacenta Leader') {
            return $this->bacenta()->region();
        }

        return null;
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

        if ($this->roles[0]->name == 'Super Admin' || $this->roles[0] == 'Bishop' || $this->roles[0] == 'General Admin') {
            return Bacenta::select();
        }

        if ($this->roles[0]->name == 'Stream Lead') {
            return $this->stream()->bacentas();
        }

        if ($this->roles[0]->name == 'Region Lead') {
            return $this->region()->bacentas();
        }

        return $this->hasMany(Bacenta::class, 'leader_id', 'id');
    }

}
