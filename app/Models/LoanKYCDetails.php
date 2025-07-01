<?php

namespace App\Models;

use App\Models\LoanApplication;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoanKYCDetails extends Model
{
    use HasFactory;

    protected $table = "loan_kyc_details";

    protected $fillable = [
        'loan_application_id',
        'pan_number',
        'aadhar_number',
        'aadhar_otp'
    ];

    public function loanApplication()
    {
        return $this->belongsTo(LoanApplication::class);
    }
}
