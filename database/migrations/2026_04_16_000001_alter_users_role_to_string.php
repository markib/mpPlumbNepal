<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'role') && DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check;');
            DB::statement('ALTER TABLE users ALTER COLUMN role TYPE VARCHAR(50) USING role::text;');
            DB::statement("ALTER TABLE users ALTER COLUMN role SET DEFAULT 'customer';");
        }
    }

    public function down(): void
    {
        // Keep the column as string rather than reverting to a Postgres enum.
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'role') && DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE users ALTER COLUMN role TYPE VARCHAR(50) USING role::text;');
        }
    }
};
