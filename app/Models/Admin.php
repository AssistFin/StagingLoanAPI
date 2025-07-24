<?php

namespace App\Models;

use App\Models\Menu;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Admin extends Authenticatable

{
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'mobile',
        'username',
        'password',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user','user_id');
    }

    public function accessibleMenus()
    {
        return Menu::whereHas('roles', function ($q) {
            $q->whereIn('roles.id', $this->roles->pluck('id'));
        })->with(['submenus' => function ($query) {
            $query->whereHas('roles', function ($q) {
                $q->whereIn('roles.id', $this->roles->pluck('id'));
            });
        }])->get();
    }

}
