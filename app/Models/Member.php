<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    //only show the following fields when returning a member as json
    protected $hidden = ['created_at', 'updated_at', 'deleted_at', 'user_id', 'church_id', 'zone_id', 'fellowship_id', 'fellowship_leader_id', 'laravel_through_key', 'micro_churches_id'];

    public function location() {
        return $this->hasOne(Location::class, 'location_id', 'id');
    }

    public function bacenta() {
        return $this->belongsTo(Bacenta::class, 'bacenta_id', 'id');
    }

    public function region() {
        return $this->belongsTo(Region::class, 'region_id', 'id');
    }

    public function zone() {
        return $this->belongsTo(Zone::class, 'zone_id', 'id');
    }

    public function stream() {
        return $this->belongsTo(Stream::class, 'stream_id', 'id');
    }

    public function attendance() {
        return $this->hasMany(MemberAttendance::class);
    }

}
