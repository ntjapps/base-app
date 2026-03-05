<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('route_analytics')) {
            Schema::create('route_analytics', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('method', 10);
                $table->string('path', 255);
                $table->string('route_name', 120)->nullable();
                $table->string('route_group', 20)->nullable();
                $table->unsignedSmallInteger('status_code');
                $table->unsignedInteger('duration_ms');
                $table->uuid('user_id')->nullable();
                $table->string('user_name')->nullable();
                $table->string('ip')->nullable();
                $table->text('user_agent')->nullable();
                $table->boolean('is_authenticated')->default(false);
                $table->timestamps();

                $table->index(['created_at']);
                $table->index(['route_name']);
                $table->index(['path']);
                $table->index(['user_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('route_analytics');
    }
};
