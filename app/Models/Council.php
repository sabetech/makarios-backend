<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Council extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    public function user(){
        return $this->belongsTo(User::class, 'leader_id', 'id');
    }

    public function bacentas() {
        return $this->hasMany(Bacenta::class, 'council_id', 'id');
    }

}
