<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CollectionConfiguration extends Model
{
    use HasFactory;

    protected $table = 'collection_configuration';

    protected $fillable = [
        'pr_off','in_off','pe_off',
    ];

}
