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
        Schema::create('wa_message_sent_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('recipient_number');
            $table->text('message_content');
            $table->string('message_id')->nullable();
            $table->boolean('preview_url')->default(false);
            $table->boolean('success')->default(false);
            $table->json('response_data')->nullable();
            $table->json('error_data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wa_message_sent_logs');
    }
};
