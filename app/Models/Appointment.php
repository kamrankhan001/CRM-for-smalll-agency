<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'appointable_id',
        'appointable_type',
        'date',
        'start_time',
        'end_time',
        'status',
        'created_by',
    ];

    protected $casts = [
        'date' => 'datetime',
        'start_time' => 'string',
        'end_time' => 'string',
    ];

    // Creator
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Polymorphic relation (Lead, Client, or Project)
    public function appointable()
    {
        return $this->morphTo();
    }

    // Attendees (many-to-many)
    public function attendees()
    {
        return $this->belongsToMany(User::class, 'appointment_user')->withTimestamps();
    }

    public function activities()
    {
        return $this->morphMany(Activity::class, 'subject')->latest();
    }
}
