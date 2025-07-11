<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Source extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'type',
        'provider',
        'account_number',
        'account_holder',
        'note',
        'user_id',
        'color_card',
        'balance'
    ];

    public function scopeCash($query)
    {
        return $query->where('type', 'cash');
    }

    public static function scopeBank($query)
    {
        return $query->where('type', 'bank');
    }
    public static function scopeEwallet($query)
    {
        return $query->where('type', 'ewallet');
    }

    public function investmentTransactions()
    {
        return $this->hasMany(InvestmentTransaction::class);
    }
}
