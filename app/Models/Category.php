<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasUuids, HasFactory;

    protected $fillable = ['id', 'name', 'user_id'];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public static function scopeTopup($query)
    {
        return $query->where('name', 'Top Up');
    }
    public static function scopeInvestment($query)
    {
        return $query->where('name', 'Investment');
    }
}
