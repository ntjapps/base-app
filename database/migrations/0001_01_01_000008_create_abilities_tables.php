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
        if (! Schema::hasTable('permission_privileges')) {
            Schema::create('permission_privileges', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('title');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('permission_menus')) {
            Schema::create('permission_menus', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('title');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permission_privileges');
        Schema::dropIfExists('permission_menus');
    }
};
