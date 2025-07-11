<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasUuids;
    protected $fillable = [
        'user_id',
        'attachment',
        'category_id',
        'source_id',
        'type',
        'description',
        'amount',
        'date',
        'name'
    ];


    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
    public function source()
    {
        return $this->belongsTo(Source::class, 'source_id');
    }
}
