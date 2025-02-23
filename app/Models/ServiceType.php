<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;
use App\Models\Church;

class ServiceType extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    protected $table = 'service_types';

    public function role() {
        return $this->belongsTo(Role::class);
    }

    public function church() {
        return $this->belongsTo(Church::class);
    }
}
