<?php

namespace App\Models;

use App\Constants\Status;
use App\Traits\Searchable;
use App\Traits\UserNotify;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable {
    use HasApiTokens, Searchable, UserNotify;


    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'mobile',
        'username',
        'password',
        'country_code',
        'status',
        'sv',
        'ver_code',
        'ver_code_send_at',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'ver_code', 'kyc_data'
    ];




    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'address' => 'object',
        'kyc_data' => 'object',
        'ver_code_send_at' => 'datetime',
        'bank_verified' => 'boolean', // Add this line
        'upi_verified' => 'boolean'
    ];

    public function loanApplications()
    {
        return $this->hasMany(LoanApplication::class);
    }

    public function loans() {
        return $this->hasMany(Loan::class);
    }
    public function deviceTokens() {
        return $this->hasMany(DeviceToken::class);
    }

    public function loginLogs() {
        return $this->hasMany(UserLogin::class);
    }

    public function transactions() {
        return $this->hasMany(Transaction::class)->orderBy('id', 'desc');
    }

    public function deposits() {
        return $this->hasMany(Deposit::class)->where('status', '!=', Status::PAYMENT_INITIATE);
    }

    public function withdrawals() {
        return $this->hasMany(Withdrawal::class)->where('status', '!=', Status::PAYMENT_INITIATE);
    }

    public function fullname(): Attribute {
        return new Attribute(
            get: fn () => $this->firstname . ' ' . $this->lastname,
        );
    }
    public function statusBadge(): Attribute {

        return new Attribute(function () {
            $html = '';
            if ($this->status == Status::USER_ACTIVE) {
                $html = createBadge('success', 'Active');
            } else {
                $html =   createBadge('danger', 'Banned');
            }
            return $html;
        });
    }

    // SCOPES
    public function scopeActive($query) {
        return $query->where('status', Status::USER_ACTIVE)->where('ev', Status::VERIFIED)->where('sv', Status::VERIFIED);
    }

    public function scopeBanned($query) {
        return $query->where('status', Status::USER_BAN);
    }

    public function scopeEmailUnverified($query) {
        return $query->where('ev', Status::UNVERIFIED);
    }

    public function scopeMobileUnverified($query) {
        return $query->where('sv', Status::UNVERIFIED);
    }

    public function scopeKycUnverified($query) {
        return $query->where('kv', Status::KYC_UNVERIFIED);
    }

    public function scopeKycPending($query) {
        return $query->where('kv', Status::KYC_PENDING);
    }

    public function scopeEmailVerified($query) {
        return $query->where('ev', Status::VERIFIED);
    }

    public function scopeMobileVerified($query) {
        return $query->where('sv', Status::VERIFIED);
    }

    public function scopeWithBalance($query) {
        return $query->where('balance', '>', 0);
    }
}
