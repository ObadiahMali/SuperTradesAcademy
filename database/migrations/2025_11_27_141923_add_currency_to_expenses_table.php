<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            // Add currency if missing (default UGX for existing rows)
            if (! Schema::hasColumn('expenses', 'currency')) {
                $table->string('currency', 3)->default('UGX')->after('amount');
            }

            // Ensure paid and paid_at exist (safe-guard)
            if (! Schema::hasColumn('expenses', 'paid')) {
                $table->boolean('paid')->default(false)->after('amount');
            }
            if (! Schema::hasColumn('expenses', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('paid');
            }
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            if (Schema::hasColumn('expenses', 'paid_at')) {
                $table->dropColumn('paid_at');
            }
            if (Schema::hasColumn('expenses', 'paid')) {
                $table->dropColumn('paid');
            }
            if (Schema::hasColumn('expenses', 'currency')) {
                $table->dropColumn('currency');
            }
        });
    }
};