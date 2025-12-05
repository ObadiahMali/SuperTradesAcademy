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
        // $table->string('phone')->nullable()->index(); // REMOVE this line
        if (!Schema::hasColumn('students', 'phone_country_code')) {
            $table->string('phone_country_code', 8)->nullable();
        }
        if (!Schema::hasColumn('students', 'plan_key')) {
            $table->string('plan_key')->nullable()->index();
        }
    });
}

public function down()
{
    Schema::table('students', function (Blueprint $table) {
        $table->dropColumn(['phone','phone_country_code','plan_key']);
    });
}
    /**
     * Reverse the migrations.
     */
 
};
