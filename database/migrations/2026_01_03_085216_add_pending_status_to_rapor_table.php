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
        // Modify enum to add 'pending' status
        DB::statement("ALTER TABLE rapor MODIFY COLUMN status ENUM('draft', 'pending', 'approved', 'published') DEFAULT 'draft'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original enum (remove 'pending')
        // First, update any 'pending' records to 'draft'
        DB::statement("UPDATE rapor SET status = 'draft' WHERE status = 'pending'");
        
        // Then modify enum back to original
        DB::statement("ALTER TABLE rapor MODIFY COLUMN status ENUM('draft', 'approved', 'published') DEFAULT 'draft'");
    }
};
