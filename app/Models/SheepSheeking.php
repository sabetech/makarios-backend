<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SheepSheeking extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];
    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    public function leader()
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function memberVisited()
    {
        return $this->belongsTo(Member::class, 'member_visited_id');
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function shepherdorialCycle()
    {
        return $this->belongsTo(ShepherdorialCycle::class);
    }
}
