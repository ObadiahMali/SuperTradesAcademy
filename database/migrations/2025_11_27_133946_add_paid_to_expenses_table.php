<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            // If your table already has a currency/amount column, add paid/paid_at after amount
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
        });
    }
};