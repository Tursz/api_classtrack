<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'learner_id',
        'date',
        'status',
        'method',
        'value',
    ];

    public function learner(): BelongsTo
    {
        return $this->belongsTo(Learner::class);
    }
}
