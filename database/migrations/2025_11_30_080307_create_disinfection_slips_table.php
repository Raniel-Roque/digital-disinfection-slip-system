<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('disinfection_slips', function (Blueprint $table) {
            $table->id();
            $table->string('slip_id')->unique();

            // Foreign relations
            $table->foreignId('truck_id')->constrained('trucks')->cascadeOnUpdate();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnUpdate();
            $table->foreignId('destination_id')->constrained('locations')->cascadeOnUpdate();
            $table->foreignId('driver_id')->constrained('drivers')->cascadeOnUpdate();

            // Reason
            $table->text('reason_for_disinfection')->nullable();

            // Attachment reference (new)
            $table->foreignId('attachment_id')->nullable()->constrained('attachments')->cascadeOnUpdate()->nullOnDelete();

            // Guards from users table
            $table->foreignId('hatchery_guard_id')->constrained('users')->cascadeOnUpdate();
            $table->foreignId('received_guard_id')->nullable()->constrained('users')->cascadeOnUpdate();

            // Status
            $table->tinyInteger('status')->default(0);
            $table->timestamp('completed_at')->nullable();
            // 0: ongoing, 1: disinfected, 2: completed

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disinfection_slips');
    }
};
