<?php
namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    // Optional: add a scope to easily get roles by hierarchy
    public function scopeOrdered($query)
    {
        return $query->orderBy('rank', 'desc');
    }
}
