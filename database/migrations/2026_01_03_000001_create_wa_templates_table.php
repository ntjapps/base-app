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
        Schema::create('wa_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('provider_id')->nullable()->comment('Meta message_template_id or hsm_id');
            $table->string('name')->comment('Template name');
            $table->string('library_template_name')->nullable();
            $table->string('language', 20);
            $table->string('category', 50);
            $table->string('sub_category', 50)->nullable();
            $table->json('components')->nullable()->comment('Template structure (header, body, footer, buttons)');
            $table->string('status', 50)->default('PENDING')->comment('APPROVED, REJECTED, PENDING, etc.');
            $table->integer('quality_score')->nullable();
            $table->text('rejected_reason')->nullable();
            $table->integer('message_send_ttl_seconds')->nullable();
            $table->boolean('cta_url_link_tracking_opted_out')->nullable();
            $table->string('parameter_format', 50)->nullable();
            $table->string('previous_category', 50)->nullable();
            $table->timestamp('last_synced_at')->nullable()->comment('Last sync from provider');
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index('provider_id');
            $table->index('name');
            $table->index('status');
            $table->index(['name', 'language']);
            $table->index('last_synced_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wa_templates');
    }
};
