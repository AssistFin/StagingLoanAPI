<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DigitapBankRequest extends Model
{
    protected $fillable = [
        'customer_id',
        'request_id',
        'txn_id',
        'token',
        'status',
        'start_upload_response',
        'status_response',
        'report_data'
    ];

    protected $casts = [
        'start_upload_response' => 'array',
        'status_response' => 'array',
        'report_data' => 'array'
    ];
}
