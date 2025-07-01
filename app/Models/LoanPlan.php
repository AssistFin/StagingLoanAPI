<?php

namespace App\Models;

use App\Traits\ApiQuery;
use App\Traits\GlobalStatus;
use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class LoanPlan extends Model
{
    use Searchable, GlobalStatus, ApiQuery;

    protected $guarded = ['id'];

    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    public function delayCharge(): Attribute
    {
        return Attribute::make(get: fn () => $this->fixed_charge + ($this->per_installment * $this->percent_charge / 100));
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function loanInterest()
    {
        return ($this->total_installment * $this->per_installment) - 100;
    }
}
