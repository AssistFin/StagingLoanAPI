<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreData extends Model
{
    use HasFactory;

    protected $table = 'bre_data';

    protected $fillable = [
        'lead_id',
        'digitap_monthly_salary',
        'digitap_confidence_score',
        'digitap_bounced_count',
        'digitap_salary_count',
        'digitap_biz_count',
        'digitap_salary_total',
        'digitap_biz_total',
        'digitap_max_monthly_credit',
        'digitap_total_days',
        'bureau_score',
        'final_decision',
        'final_approved_amount',
        'digitap_result',
        'experian_result',
        'monthly_salary_check_result',
        'final_result',
    ];

    protected $casts = [
        'digitap_result' => 'array',
        'experian_result' => 'array',
        'monthly_salary_check_result' => 'array',
        'final_result' => 'array',
    ];
}
