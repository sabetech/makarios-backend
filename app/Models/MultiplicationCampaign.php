<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MultiplicationCampaign extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];
    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function leader()
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function shepherdorialCycle()
    {
        return $this->belongsTo(ShepherdorialCycle::class);
    }
}
