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
    Schema::table('students', function (Blueprint $table) {
        $table->string('plan_key')->nullable()->after('dob');
        $table->decimal('price', 10, 2)->nullable()->after('plan_key');
        $table->string('currency', 6)->default('USD')->after('price');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            //
        });
    }
};
