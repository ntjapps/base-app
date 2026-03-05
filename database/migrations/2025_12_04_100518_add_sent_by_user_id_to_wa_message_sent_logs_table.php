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
        Schema::table('wa_message_sent_logs', function (Blueprint $table) {
            $table->foreignUuid('sent_by_user_id')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wa_message_sent_logs', function (Blueprint $table) {
            $table->dropForeign(['sent_by_user_id']);
            $table->dropColumn('sent_by_user_id');
        });
    }
};
