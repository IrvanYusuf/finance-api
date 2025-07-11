<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class InvestmentTransaction extends Model
{
    use HasUuids;

    protected $fillable = [
        'id',
        'user_id',
        'source_id',
        'investment_id',
        'amount',
        'transaction_date',
        'notes'
    ];

    public function source()
    {
        return $this->belongsTo(Source::class, 'source_id');
    }
    public function investment()
    {
        return $this->belongsTo(Investment::class, 'investment_id');
    }
}
