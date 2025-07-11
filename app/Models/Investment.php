<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Investment extends Model
{
    use HasUuids;

    protected $fillable = [
        'id',
        'user_id',
        'name',
        'type',
        'target_amount',
        'due_date',
        'purchase_amount',
        'current_value',
        'expected_return',
        'saved_amount',
        'notes'
    ];
    public function InvestmentTransactions()
    {
        return $this->hasMany(InvestmentTransaction::class);
    }
}
