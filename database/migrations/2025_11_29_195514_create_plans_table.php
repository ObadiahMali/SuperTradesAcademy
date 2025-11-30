<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
 public function up()
{
    Schema::create('plans', function (Blueprint $table) {
        $table->id();
        $table->string('key')->unique();   // e.g. signals_monthly_1
        $table->string('label');           // e.g. Signals 1 month
        $table->decimal('price', 10, 2);   // e.g. 59.00
        $table->string('currency', 3)->default('USD');
        $table->timestamps();
    });
}

public function down()
{
    Schema::dropIfExists('plans');
}

   
};
