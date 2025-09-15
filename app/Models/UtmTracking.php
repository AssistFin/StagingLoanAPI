<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UtmTracking extends Model
{
    use HasFactory;

    protected $table = 'utm_tracking';   // 👈 specify table name
    protected $fillable = [
        'user_id',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
