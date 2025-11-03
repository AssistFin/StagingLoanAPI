<?php

namespace App\Models;

use App\Models\User;
use App\Models\LoanApproval;
use App\Models\LoanDocument;
use App\Models\LoanDisbursal;
use App\Models\UtrCollection;
use App\Models\LoanKYCDetails;
use App\Models\LoanBankDetails;
use App\Models\LoanAddressDetails;
use App\Models\LoanPersonalDetails;
use App\Models\LoanEmploymentDetails;
use App\Models\DigitapBankRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoanApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'loan_no',
        'loan_amount',
        'purpose_of_loan',
        'running_loan',
        'status',
        'current_step',
        'next_step'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function personalDetails()
    {
        return $this->hasOne(LoanPersonalDetails::class, 'loan_application_id');
    }

    public function employmentDetails()
    {
        return $this->hasOne(LoanEmploymentDetails::class, 'loan_application_id');
    }

    public function kycDetails()
    {
        return $this->hasOne(LoanKYCDetails::class, 'loan_application_id');
    }
    
    public function loanDocument()
    {
        return $this->hasOne(LoanDocument::class, 'loan_application_id');
    }

    public function addressDetails()
    {
        return $this->hasOne(LoanAddressDetails::class, 'loan_application_id');
    }

    public function bankDetails()
    {
        return $this->hasOne(LoanBankDetails::class, 'loan_application_id');
    }

    public function collections()
    {
        return $this->hasMany(UtrCollection::class, 'loan_application_id');
    }

    public function loanApproval()
    {
        return $this->hasOne(LoanApproval::class, 'loan_application_id');
    }

    public function loanDisbursal()
    {
        return $this->hasOne(LoanDisbursal::class, 'loan_application_id');
    }

    public function cashfreeEnachRequests()
    {
        return $this->hasMany(CashfreeEnachRequestResponse::class, // model name
            'subscription_id', // foreign key in cashfree table
            'loan_no'          // local key in loan_applications
        );
    }

    public function experianCreditReport()
    {
        return $this->hasOne(ExperianCreditReport::class, 'lead_id');
    }

    public function digitapRequest()
    {
        return $this->hasOne(DigitapBankRequest::class, 'customer_id', 'id');
    }
}
