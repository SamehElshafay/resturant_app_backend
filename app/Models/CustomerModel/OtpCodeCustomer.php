<?php

namespace App\Models\CustomerModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtpCodeCustomer extends Model {
    use HasFactory;

    protected $table = 'otp_code_customer';

    protected $fillable = [
        'code',
        'user_id',
    ];
}