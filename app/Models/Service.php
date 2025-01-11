<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
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
