<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Admin;
use App\Models\User;

class AdminEventLog extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'admin_id', 'user_id', 'event', 'description', 'ip_address', 'user_agent'
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
