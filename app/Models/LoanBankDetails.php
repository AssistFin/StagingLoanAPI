<?php

namespace App\Models;

use App\Models\LoanApplication;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoanBankDetails extends Model
{
    use HasFactory;

    protected $table = "loan_bank_details";

    protected $fillable = [
        'loan_application_id',
        'bank_name',
        'account_number',
        'ifsc_code',
        'account_holder_name',
        'bank_statement',
        'bank_statement_password'
    ];

    public function loanApplication()
    {
        return $this->belongsTo(LoanApplication::class);
    }
}
