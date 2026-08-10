<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Streak extends Model
{
    protected $fillable = ['user_id', 'current_streak', 'last_activity_date'];
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
