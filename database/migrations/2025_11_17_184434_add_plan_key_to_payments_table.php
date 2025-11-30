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
    Schema::table('payments', function (Blueprint $table) {
        if (!Schema::hasColumn('payments', 'plan_key')) {
            $table->string('plan_key')->nullable()->after('currency');
        }
    });
}

public function down()
{
    Schema::table('payments', function (Blueprint $table) {
        if (Schema::hasColumn('payments', 'plan_key')) {
            $table->dropColumn('plan_key');
        }
    });
}
};
