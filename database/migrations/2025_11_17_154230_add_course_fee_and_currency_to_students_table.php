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
        // Remove this line if currency already exists
        // $table->string('currency', 10)->default('UGX');

        // Keep only the missing column
        if (!Schema::hasColumn('students', 'course_fee')) {
            $table->decimal('course_fee', 10, 2)->nullable();
        }
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
