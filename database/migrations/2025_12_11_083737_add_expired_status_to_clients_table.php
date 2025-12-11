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
        // Modify the enum to include 'expired'
        DB::statement("ALTER TABLE clients MODIFY COLUMN status ENUM('pending', 'active', 'banned', 'expired') DEFAULT 'pending'");
        
        // Update clients with expired activation to 'expired' status
        DB::table('clients')
            ->where('status', 'active')
            ->whereNotNull('activation_expires_at')
            ->where('activation_expires_at', '<', now())
            ->update(['status' => 'expired']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Convert expired clients back to active before removing the enum value
        DB::table('clients')
            ->where('status', 'expired')
            ->update(['status' => 'active']);
        
        // Revert enum to original values
        DB::statement("ALTER TABLE clients MODIFY COLUMN status ENUM('pending', 'active', 'banned') DEFAULT 'pending'");
    }
};
