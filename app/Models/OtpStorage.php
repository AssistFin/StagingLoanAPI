<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtpStorage extends Model
{
    use HasFactory;

    protected $table = 'otp_storage'; // Specify the table name if it's not the pluralized form of the model name

    protected $fillable = [
        'mobile',
        'email',
        'otp_code',
        'expires_at',
        'attempts',
    ];

    public $timestamps = true; // Enable timestamps if not already

    protected $casts = [
        'expires_at' => 'datetime', // Ensure expires_at is treated as a datetime object
    ];
}
