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
        Schema::connection('logs')->create('logs', function (Blueprint $table) {
            $table->id();
            
            // User information (stored directly, not as foreign key)
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('user_first_name')->nullable();
            $table->string('user_middle_name')->nullable();
            $table->string('user_last_name')->nullable();
            $table->string('user_username')->nullable();
            $table->integer('user_type')->nullable(); // 0: guard, 1: admin, 2: superadmin
            
            // Action details
            $table->string('action', 50)->index(); // create, update, delete, restore, etc.
            $table->string('model_type')->nullable()->index(); // App\Models\DisinfectionSlip, App\Models\User, etc.
            $table->unsignedBigInteger('model_id')->nullable()->index(); // ID of the affected model
            $table->text('description')->nullable(); // Human-readable description
            
            // Detailed change information (JSON format)
            $table->json('changes')->nullable(); // Detailed changes: old_values, new_values, field_changes, etc.
            
            // Request information
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            
            $table->timestamps();

            // Index for efficient queries
            $table->index(['model_type', 'model_id']);
            $table->index(['user_id', 'created_at']);
            $table->index(['action', 'created_at']);
            $table->index(['user_type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('logs')->dropIfExists('logs');
    }
};
