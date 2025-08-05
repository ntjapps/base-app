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
        Schema::create('wa_api_message_threads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('phone_number')->index();
            $table->uuidMorphs('messageable');
            $table->timestamp('last_message_at');
            $table->timestamps();

            // Add an index for faster phone number based thread queries
            $table->index(['phone_number', 'last_message_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wa_api_message_threads');
    }
};
