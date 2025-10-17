<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'causer_id',
        'action',
        'subject_id',
        'subject_type',
        'description',
        'changes',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    /**
     * The user who performed the action
     */
    public function causer()
    {
        return $this->belongsTo(User::class, 'causer_id');
    }

    /**
     * The model on which the action was performed
     */
    public function subject()
    {
        return $this->morphTo();
    }
}
