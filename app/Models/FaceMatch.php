<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FaceMatch extends Model
{
    protected $table = 'face_matches';

    protected $fillable = [
        'user_id',
        'loan_application_id',
        'person_image',
        'card_image',
        'request_payload',
        'response_payload',
        'is_match',
        'confidence',
        'attempt_count',
        'attempt_date',
        'final_status'
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
        'is_match' => 'boolean',
    ];
}