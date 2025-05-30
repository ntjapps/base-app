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
        if (! Schema::hasTable('log')) {
            Schema::create('log', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->text('message');
                $table->string('channel');
                $table->unsignedSmallInteger('level')->default(0);
                $table->string('level_name', 20);
                $table->string('datetime');
                $table->text('context');
                $table->text('extra');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log');
    }
};
