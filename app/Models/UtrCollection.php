<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UtrCollection extends Model
{
    use HasFactory;

    protected $table = 'utr_collections';

    protected $fillable = [
        'loan_application_id',
        'user_id',
        'principal',
        'interest',
        'penal',
        'overdue_intrest',
        'collection_amt',
        'collection_date',
        'mode',
        'discount_principal',
        'discount_interest',
        'discount_penal',
        'payment_id',
        'status',
        'created_by'
    ];

    // Relationships
    public function loanApplication()
    {
        return $this->belongsTo(LoanApplication::class, 'loan_application_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
