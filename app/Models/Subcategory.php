<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subcategory extends Model
{
    protected $fillable = ['name', 'category_id'];
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function allowedTopics(): HasMany
    {
        return $this->hasMany(AllowedTopic::class);
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }

    public function points(): HasMany
    {
        return $this->hasMany(UserSubcategoryPoint::class);
    }

    public function recommendations(): HasMany
    {
        return $this->hasMany(Recommendation::class);
    }

    //
}
