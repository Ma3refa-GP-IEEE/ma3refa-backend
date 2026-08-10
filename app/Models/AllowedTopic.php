<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class AllowedTopic extends Model
{
    
protected $fillable = ['subcategory_id', 'topic_name'];

public function subcategory(): BelongsTo
{
    return $this->belongsTo(Subcategory::class);
}

public function questions(): HasMany
{
    return $this->hasMany(Question::class);
}

public function recommendations(): HasMany
{
    return $this->hasMany(Recommendation::class);
}
}
