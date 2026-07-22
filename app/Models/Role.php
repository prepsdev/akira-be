<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function permissions()
    {
        return $this->hasMany(RolePermission::class);
    }

    public function hasPermission($menuName)
    {
        return $this->permissions()
            ->where('menu_name', $menuName)
            ->where('can_access', true)
            ->exists();
    }
}
