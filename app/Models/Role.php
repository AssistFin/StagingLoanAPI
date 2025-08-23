<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['name'];

    public function users() {
        return $this->belongsToMany(User::class);
    }

    public function menus() {
        return $this->belongsToMany(Menu::class);
    }

    public function submenus() {
        return $this->belongsToMany(Submenu::class, 'submenu_role');
    }

    public function admins()
    {
        return $this->belongsToMany(Admin::class, 'role_user', 'role_id', 'user_id');
    }
}
