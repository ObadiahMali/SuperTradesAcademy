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
     Schema::create('students', function (Blueprint $table) {
    $table->id();
    $table->foreignId('intake_id')->constrained()->cascadeOnDelete();
    $table->string('first_name');
    $table->string('last_name')->nullable();
    $table->string('phone')->nullable();
    $table->string('email')->nullable();
    $table->string('id_number')->nullable();
    $table->date('dob')->nullable();
    $table->string('status')->default('active');
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
