<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    public function location() {
        return $this->hasOne(Location::class, 'location_id', 'id');
    }

    public function bacenta() {
        return $this->belongsTo(Bacenta::class, 'bacenta_id', 'id');
    }

    public function region() {
        return $this->belongsTo(Region::class, 'region_id', 'id');
    }

}
