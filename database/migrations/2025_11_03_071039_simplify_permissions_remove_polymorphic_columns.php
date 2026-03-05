<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop polymorphic columns if they exist
        if (Schema::hasColumns('permissions', ['ability_type', 'ability_id'])) {
            Schema::table('permissions', function (Blueprint $table) {
                $table->dropMorphs('ability');
            });
        }

        // Drop the permission menu and privilege tables if they exist
        Schema::dropIfExists('permission_menus');
        Schema::dropIfExists('permission_privileges');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate polymorphic columns only if they don't exist
        if (! Schema::hasColumns('permissions', ['ability_type', 'ability_id'])) {
            Schema::table('permissions', function (Blueprint $table) {
                $table->nullableUuidMorphs('ability');
            });
        }

        // Recreate permission menu table only if it doesn't exist
        if (! Schema::hasTable('permission_menus')) {
            Schema::create('permission_menus', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('title');
                $table->timestamps();
            });
        }

        // Recreate permission privilege table only if it doesn't exist
        if (! Schema::hasTable('permission_privileges')) {
            Schema::create('permission_privileges', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('title');
                $table->timestamps();
            });
        }
    }
};
