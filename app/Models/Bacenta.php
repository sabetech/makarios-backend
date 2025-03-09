<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bacenta extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $guarded = ['id'];

    public function zone(){
        return $this->belongsTo(Zone::class);
    }

    public function leader(){
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function members(){
        return $this->hasMany(Member::class);
    }

    public function region() {
        return $this->belongsTo(Region::class, 'region_id', 'id');
    }

    public function services(){
        return $this->hasMany(Service::class, 'bacenta_id', 'id');
    }

}
