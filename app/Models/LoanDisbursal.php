<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanDisbursal extends Model
{
    use HasFactory;

    protected $table = 'loan_disbursals';

    protected $fillable = [
        'loan_application_id',
        'user_id',
        'loan_disbursal_number',
        'disbursal_amount',
        'account_no',
        'ifsc',
        'account_type',
        'bank_name',
        'branch',
        'cheque_no',
        'disbursal_date',
        'final_remark',
        'disbursed_by',
        'utr_no',
        'enach_reference_number'
    ];

    // Relationship with LoanApproval
    public function loanApproval()
    {
        return $this->belongsTo(LoanApproval::class, 'loan_application_id');
    }

    // Relationship with User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
