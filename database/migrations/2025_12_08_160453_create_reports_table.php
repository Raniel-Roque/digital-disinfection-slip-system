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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            
            // Foreign relations
            $table->foreignId('user_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            // slip_id is nullable to support both slip-related reports and miscellaneous reports
            $table->foreignId('slip_id')->nullable()->constrained('disinfection_slips')->cascadeOnUpdate()->cascadeOnDelete();
            
            // Report details
            $table->text('description');
            $table->timestamp('resolved_at')->nullable();
            // Track who resolved the report (admin/superadmin user_id)
            $table->foreignId('resolved_by')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
