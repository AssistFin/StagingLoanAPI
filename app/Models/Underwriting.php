<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Underwriting extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_id', 'loan_id', 'user_id', 'salary_month1', 'salary_month2', 'salary_month3', 'average_salary', 'bank_score', 'min_balance', 'avg_balance', 'bounce_1_month', 'bounce_3_month', 'bureau_score', 'dpd_30_1', 'dpd_30_amt1', 'dpd_30_2', 'dpd_30_amt2', 'dpd_90_1', 'dpd_90_amt1', 'dpd_90_2', 'dpd_90_amt2', 'unsecured_loan_experience', 'loan_open_6m', 'loan_closed_6m', 'last2_open_1', 'last2_open_2', 'last2_closed_1', 'last2_closed_2', 'leverage_avg_salary', 'leverage_unsecured_loan', 'status'
    ];
}
