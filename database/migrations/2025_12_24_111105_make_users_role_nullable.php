<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::beginTransaction();

        // 1) Create a temporary backup table
        Schema::create('users_temp_backup', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        // 2) Copy data from users to backup (if users exists)
        if (Schema::hasTable('users')) {
            $cols = 'id,name,email,email_verified_at,password,role,remember_token,created_at,updated_at';
            DB::statement("INSERT INTO users_temp_backup ($cols) SELECT $cols FROM users");
        }

        // 3) Drop the old users table
        Schema::dropIfExists('users');

        // 4) Recreate users table with role nullable and no enum/check/default
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role')->nullable()->default(null);
            $table->rememberToken();
            $table->timestamps();
        });

        // 5) Copy data back from backup
        if (Schema::hasTable('users_temp_backup')) {
            $cols = 'id,name,email,email_verified_at,password,role,remember_token,created_at,updated_at';
            DB::statement("INSERT INTO users ($cols) SELECT $cols FROM users_temp_backup");
        }

        // 6) Drop the temporary backup table
        Schema::dropIfExists('users_temp_backup');

        DB::commit();
    }

    public function down(): void
    {
        // Reverting this safely is complex; implement if you need a down migration.
        Schema::dropIfExists('users');
    }
};
