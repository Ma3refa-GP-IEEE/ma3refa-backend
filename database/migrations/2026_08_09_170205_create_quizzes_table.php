<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('subcategory_id')->constrained('subcategories');
            $table->enum('difficulty', ['easy', 'medium', 'hard']);
            $table->integer('score');
            $table->integer('total_questions');
            $table->boolean('included_in_recommendation_batch')->default(false);
            $table->timestamp('created_at');
            $table->timestamp('finished_at')->nullable();
           
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};
