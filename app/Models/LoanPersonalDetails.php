<?php

namespace App\Models;

use App\Models\LoanApplication;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoanPersonalDetails extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_application_id',
        'date_of_birth',
        'pin_code',
        'city',
        'employment_type',
        'monthly_income',
        'income_received_in'
    ];

    public function loanApplication()
    {
        return $this->belongsTo(LoanApplication::class);
    }
}
