<?php

namespace App\Models;

use App\Traits\ApiQuery;
use Illuminate\Database\Eloquent\Model;

class UserNotification extends Model {
    use ApiQuery;
    protected $guarded = [];
}
