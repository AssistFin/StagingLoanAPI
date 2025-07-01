<?php

namespace App\Models;

use App\Models\LoanApplication;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoanEmploymentDetails extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_application_id',
        'residence_type',
        'company_name',
        'designation',
        'email',
        'residence_address',
        'office_address',
        'education_qualification',
        'marital_status',
        'work_experience_years'
    ];

    public function loanApplication()
    {
        return $this->belongsTo(LoanApplication::class);
    }
}
