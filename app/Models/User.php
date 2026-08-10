<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;


#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */

    protected $fillable = ['name', 'email', 'password', 'age', 'gender'];
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function streak(): HasOne
    {
        return $this->hasOne(Streak::class);
    }
    
    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }
    
    public function userAnswers(): HasMany
    {
        return $this->hasMany(UserAnswer::class);
    }
    
    public function subcategoryPoints(): HasMany
    {
        return $this->hasMany(UserSubcategoryPoint::class);
    }
    
    public function recommendations(): HasMany
    {
        return $this->hasMany(Recommendation::class);
    }


}
