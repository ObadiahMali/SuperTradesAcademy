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
    Schema::table('intakes', function (Blueprint $table) {
        $table->decimal('expected_amount', 14, 2); // adjust after() as needed
    });
}

public function down()
{
    Schema::table('intakes', function (Blueprint $table) {
        $table->dropColumn('expected_amount');
    });
}
};
