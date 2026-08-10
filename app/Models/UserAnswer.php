<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class UserAnswer extends Model
{
    
protected $fillable = ['user_id', 'quiz_id', 'question_id', 'selected_answer', 'is_correct', 'answered_at'];
public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}

public function quiz(): BelongsTo
{
    return $this->belongsTo(Quiz::class);
}

public function question(): BelongsTo
{
    return $this->belongsTo(Question::class);
}
}
