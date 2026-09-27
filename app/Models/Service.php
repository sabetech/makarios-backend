<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];
    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];
    protected $table = 'services';

    public function serviceType(){
        return $this->belongsTo(ServiceType::class);
    }

    public function church(){
        return $this->belongsTo(Church::class);
    }

    public function stream(){
        return $this->belongsTo(Stream::class);
    }

    public function region(){
        return $this->belongsTo(Region::class);
    }

    public function zone(){
        return $this->belongsTo(Zone::class);
    }

    public function bacenta(){
        return $this->belongsTo(Bacenta::class);
    }

}
