<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Add nullable columns so existing rows are fine
            if (!Schema::hasColumn('payments', 'verification_hash')) {
                $table->string('verification_hash', 64)->nullable()->after('receipt_number');
            }
            if (!Schema::hasColumn('payments', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('plan_key');
                $table->index('created_by');
            }
            if (!Schema::hasColumn('payments', 'created_by_name')) {
                $table->string('created_by_name')->nullable()->after('created_by');
            }
            // If you want to persist converted currency/amount ensure columns exist
            if (!Schema::hasColumn('payments', 'amount_converted')) {
                $table->decimal('amount_converted', 16, 4)->nullable()->after('amount');
            }
            if (!Schema::hasColumn('payments', 'converted_currency')) {
                $table->string('converted_currency', 8)->nullable()->after('amount_converted');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['verification_hash', 'created_by_name', 'amount_converted', 'converted_currency']);
            if (Schema::hasColumn('payments', 'created_by')) {
                $table->dropIndex(['created_by']);
                $table->dropColumn('created_by');
            }
        });
    }
};