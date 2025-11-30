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
        $table->string('address_line1')->nullable();
        $table->string('address_line2')->nullable();
        $table->string('city')->nullable();
        $table->string('region')->nullable();
        $table->string('postal_code')->nullable();
        $table->string('country')->nullable();

        $table->string('email_verification_token')->nullable()->index();
        $table->timestamp('email_verification_sent_at')->nullable();
        $table->timestamp('email_verified_at')->nullable();
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
