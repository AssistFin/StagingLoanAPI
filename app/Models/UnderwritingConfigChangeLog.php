<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnderwritingConfigChangeLog extends Model
{
    use HasFactory;

    protected $table = 'underwriting_config_change_logs';

    protected $fillable = [
        'admin_id','old_value','new_value','remark',
    ];

    public function admin()
    {
        return $this->belongsTo(\App\Models\Admin::class, 'admin_id');
    }

}
