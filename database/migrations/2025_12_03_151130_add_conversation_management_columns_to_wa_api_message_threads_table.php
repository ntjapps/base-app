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
        Schema::table('wa_api_message_threads', function (Blueprint $table) {
            $table->string('status', 20)->default('OPEN')->after('last_message_at');
            $table->foreignUuid('assigned_agent_id')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('handoff_requested_at')->nullable()->after('assigned_agent_id');

            $table->index('status');
            $table->index(['status', 'assigned_agent_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wa_api_message_threads', function (Blueprint $table) {
            $table->dropForeign(['assigned_agent_id']);
            $table->dropIndex(['status', 'assigned_agent_id']);
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'assigned_agent_id', 'handoff_requested_at']);
        });
    }
};
