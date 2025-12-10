<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // Add status column
            $table->enum('status', ['pending', 'active', 'banned'])->default('pending')->after('device_token');
            // Add activation_expires_at column
            $table->timestamp('activation_expires_at')->nullable()->after('status');
        });

        // Migrate existing data: convert is_active to status
        DB::table('clients')->where('is_active', true)->update(['status' => 'active']);
        DB::table('clients')->where('is_active', false)->update(['status' => 'pending']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['status', 'activation_expires_at']);
        });
    }
};
