<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kyc extends Model
{
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'date_of_birth',
        'id_type',
        'id_number',
        'id_document',
        'status',
        'verified_at'
    ];

     public function user()
    {
        return $this->belongsTo(User::class);
    }
}
