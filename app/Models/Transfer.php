<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transfer extends Model
{
    protected $fillable = [
        'user_id', 'amount', 'unique_request_number', 'beneficiary_name',
        'account_number', 'ifsc', 'upi_handle', 'payment_mode', 'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
