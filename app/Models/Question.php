<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    protected $fillable = ['description', 'level', 'option_a', 'option_b', 'option_c', 'option_d', 'correct_answer', 'explanation', 'allowed_topic_id', 'created_at'];
    public $timestamps = false;
    public function allowedTopic(): BelongsTo
    {
        return $this->belongsTo(AllowedTopic::class);
    }

    public function quizzes(): BelongsToMany
    {
        return $this->belongsToMany(Quiz::class, 'quiz_questions')
            ->withPivot('question_order');
    }

    public function userAnswers(): HasMany
    {
        return $this->hasMany(UserAnswer::class);
    }
}
