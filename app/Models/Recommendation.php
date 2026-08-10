<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Recommendation extends Model
{

    protected $fillable = ['user_id', 'subcategory_id', 'allowed_topic_id', 'difficulty', 'created_at'];
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function allowedTopic(): BelongsTo
    {
        return $this->belongsTo(AllowedTopic::class);
    }
}
