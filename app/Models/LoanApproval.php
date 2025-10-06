<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanApproval extends Model
{
    use HasFactory;

    protected $table = 'loan_approvals';

    protected $fillable = [
        'loan_application_id',
        'user_id',
        'loan_number',
        'loan_type',
        'branch',
        'loan_tenure_days',
        'loan_tenure_date',
        'tentative_disbursal_date',
        'gst_amount',
        'processing_fee_amount',
        'approval_amount',
        'repayment_amount',
        'roi',
        'salary_date',
        'repay_date',
        'processing_fee',
        'disbursal_amount',
        'gst',
        'email',
        'cibil_score',
        'monthly_income',
        'status',
        'credited_by',
        'approval_date',
        'final_remark',
        'additional_remark',
        'loan_purpose',
        'created_at',
        'updated_at',
        'kfs_path'
    ];

    // Relationship with Loan Application
    public function loanApplication()
    {
        return $this->belongsTo(LoanApplication::class, 'loan_application_id');
    }

    // Relationship with User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creditedBy()
    {
        return $this->belongsTo(Admin::class, 'credited_by', 'id');
    }

}
