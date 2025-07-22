<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashfreeEnachRequestResponse extends Model
{
    use HasFactory;

    protected $table = 'cashfree_enach_request_response_data';

    protected $fillable = [
        'subscription_id'
    ];

}
