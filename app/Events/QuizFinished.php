<?php

namespace App\Events;

use App\Models\Quiz;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuizFinished
{
    use Dispatchable, SerializesModels;

    public function __construct(public Quiz $quiz)
    {
    }
}