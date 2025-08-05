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
        Schema::create('wa_message_webhook_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('phone_number_id')->nullable();
            $table->string('display_phone_number')->nullable();
            $table->string('contact_wa_id')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('message_id')->nullable();
            $table->string('message_from')->nullable();
            $table->string('message_type')->nullable();
            $table->text('message_body')->nullable();
            $table->string('timestamp')->nullable();
            $table->json('raw_data');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wa_message_webhook_logs');
    }
};
