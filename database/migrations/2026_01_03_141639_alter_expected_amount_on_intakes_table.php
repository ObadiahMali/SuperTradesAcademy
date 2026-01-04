<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('intakes', function (Blueprint $table) {
            $table->decimal('expected_amount', 14, 2)->default(0)->change();
        });
    }

    public function down()
    {
        Schema::table('intakes', function (Blueprint $table) {
            // revert to no default and not nullable if that was original
            $table->decimal('expected_amount', 14, 2)->default(null)->nullable()->change();
        });
    }
};
