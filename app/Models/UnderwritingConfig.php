<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnderwritingConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'avgSalary', 'minBalance', 'avgBalance', 'bankScore', 'bounceLast1Month', 'bounceLast3Month', 'bureauScore', 'dpdLast30Days', 'dpdamtLast30Days', 'dpdLast90Days', 'dpdamtLast90Days', 'expUnsecureLoan', 'leverage', 'exposureOnSalary'
    ];
}
