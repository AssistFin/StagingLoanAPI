<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExperianCreditReport extends Model
{
    protected $table = 'experian_credit_reports'; // table name

    // Relation with LoanApplication
    public function loanApplication()
    {
        return $this->belongsTo(LoanApplication::class, 'loan_application_id');
    }

}
