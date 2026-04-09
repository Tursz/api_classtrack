<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Learner extends Model
{
    protected $fillable = [
        'user_id',
        'plan_id',
        'name',
        'email',
        'contact',
        'observation',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lesson(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }

    public function payment(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function classroom(): HasMany
    {
        return $this->hasMany(Classroom::class);
    }
}
