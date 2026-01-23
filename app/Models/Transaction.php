<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'reference',
        'sender_account_id',
        'receiver_account_id',
        'amount',
        'type',
        'status'
    ];

    public function sender()
    {
        return $this->belongsTo(Account::class, 'sender_account_id');
    }

    public function receiver()
    {
        return $this->belongsTo(Account::class, 'receiver_account_id');
    }
}
