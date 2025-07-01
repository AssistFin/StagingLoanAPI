<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanPayment extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'loan_payments';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'loan_application_id',
        'loan_disbursal_id',
        'loan_application_no',
        'loan_disbursal_no',
        'payment_reference',
        'name',
        'email',
        'mobile',
        'current_repayment_amount',
        'repayment_amount',
        'loan_amount',
        'overdue_amount',
        'interestAmount',
        'penalAmount',
        'currency',
        'status',
        'cf_order_id',
        'cf_payment_id',
        'payment_request',
        'payment_response',
        'payment_details'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'payment_request' => 'array',
        'payment_response' => 'array',
        'payment_details' => 'array',
        'repayment_amount' => 'decimal:2',
        'payment_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        // Add any sensitive fields you want to hide
    ];

    /**
     * Get the user associated with the payment.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the loan application associated with the payment.
     */
    public function application()
    {
        return $this->belongsTo(LoanApplication::class, 'loan_application_id');
    }

    /**
     * Get the loan disbursal associated with the payment.
     */
    public function disbursal()
    {
        return $this->belongsTo(LoanDisbursal::class, 'loan_disbursal_id');
    }

    /**
     * Scope a query to only include pending payments.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include successful payments.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope a query to only include failed payments.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Check if payment is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if payment is successful.
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }

    /**
     * Check if payment is failed.
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Get the payment reference with prefix.
     */
    public function getFormattedReferenceAttribute(): string
    {
        return 'LN-PAY-' . $this->payment_reference;
    }
}