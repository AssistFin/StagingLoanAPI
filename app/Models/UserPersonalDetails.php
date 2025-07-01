<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class UserPersonalDetails extends Model
{
    protected $table = 'user_personal_details'; // Specify your table name here

    protected $fillable = [
        'user_id', 'full_name', 'date_of_birth', 'gender', 'mobile_number', 'email',
        'marital_status', 'spouse_name', 'number_of_kids', 'mother_name',
        'qualification', 'pan_number', 'aadhar_number', 'purpose_of_loan','eligibility_amount','cibilscore'
    ];
}
