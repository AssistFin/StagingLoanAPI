<?php

namespace App\Models;

use App\Models\LoanApplication;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoanAddressDetails extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_application_id',
        'address_type',
        'house_no',
        'locality',
        'pincode',
        'city',
        'state',
        'relation',
        'relative_name',
        'contact_number'
    ];

    public function loanApplication()
    {
        return $this->belongsTo(LoanApplication::class);
    }
}
