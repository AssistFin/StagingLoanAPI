<?php

namespace App\Models;
use App\Models\Menu;
use App\Models\Role;
use Illuminate\Database\Eloquent\Model;

class Submenu extends Model
{
    protected $fillable = ['menu_id', 'name', 'slug', 'route'];

    public function menu() {
        return $this->belongsTo(Menu::class);
    }

    public function roles() {
        return $this->belongsToMany(Role::class, 'submenu_role', 'submenu_id', 'role_id');
    }
}
