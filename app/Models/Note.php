<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    use HasFactory;

    protected $fillable = [
        'content', 'noteable_id', 'noteable_type', 'user_id',
    ];

    public function noteable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function activities()
    {
        return $this->morphMany(Activity::class, 'subject')->latest();
    }
}
