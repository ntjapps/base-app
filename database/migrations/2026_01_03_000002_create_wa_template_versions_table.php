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
        Schema::create('wa_template_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('wa_template_id')->constrained('wa_templates')->cascadeOnDelete();
            $table->integer('version')->default(1)->comment('Incremental version number');
            $table->json('snapshot')->comment('Full template state at this version');
            $table->foreignUuid('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('change_reason')->nullable()->comment('Reason for change');
            $table->json('provider_event')->nullable()->comment('Webhook event that triggered this version');
            $table->timestamp('created_at');

            // Indexes
            $table->index('wa_template_id');
            $table->index('version');
            $table->index(['wa_template_id', 'version']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wa_template_versions');
    }
};
