<?php

namespace App\Models;

use App\Models\LoanApplication;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoanDocument extends Model
{
    use HasFactory;

    protected $table = 'loan_documents';

    protected $fillable = [
        'loan_application_id',
        'selfie_image'
    ];

    public function loanApplication()
    {
        return $this->belongsTo(LoanApplication::class);
    }
}
