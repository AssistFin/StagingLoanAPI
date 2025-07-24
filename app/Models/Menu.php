<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $fillable = ['name', 'slug', 'icon', 'route'];

    public function submenus() {
        return $this->hasMany(Submenu::class);
    }

    public function roles() {
        return $this->belongsToMany(Role::class, 'menu_role', 'menu_id', 'role_id');
    }
}
