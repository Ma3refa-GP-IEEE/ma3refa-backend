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
        Schema::create('user_subcategory_points', function (Blueprint $table) {
            $table->id(); 
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('subcategory_id')->constrained('subcategories');
            $table->integer('total_points');
            $table->unique(['user_id', 'subcategory_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_subcategory_points');
    }
};
