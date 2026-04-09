<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lesson extends Model
{
    protected $fillable = [
        'learner_id',
        'date',
        'start_at',
        'end_at'
    ];

    public function learner(): BelongsTo
    {
        return $this->belongsTo(Learner::class);
    }
}
