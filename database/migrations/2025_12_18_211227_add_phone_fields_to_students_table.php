<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPhoneFieldsToStudentsTable extends Migration
{
    public function up()
    {
        Schema::table('students', function (Blueprint $table) {
            // $table->string('phone_country_code', 16)->nullable()->after('phone');
            $table->string('phone_full', 32)->nullable()->after('phone_country_code');
            $table->string('phone_display', 64)->nullable()->after('phone_full');
            $table->string('phone_dial', 16)->nullable()->after('phone_display');
        });
    }

    public function down()
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['phone_country_code', 'phone_full', 'phone_display', 'phone_dial']);
        });
    }
}