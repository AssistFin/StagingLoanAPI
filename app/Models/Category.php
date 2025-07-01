<?php

namespace App\Models;

use App\Traits\GlobalStatus;
use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use Searchable, GlobalStatus;

    public function plans()
    {
        return $this->hasMany(LoanPlan::class);
    }
}
