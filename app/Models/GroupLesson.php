<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupLesson extends Model
{
    protected $fillable = [
        'group_id',
        'date',
        'start_at',
        'end_at',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }
}
