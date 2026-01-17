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
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('destination_id')->constrained('locations')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained('drivers')->cascadeOnUpdate()->cascadeOnDelete();
 
            // Reason - nullable, with foreign key constraint
            $table->foreignId('reason_id')->nullable()->constrained('reasons')->cascadeOnUpdate()->nullOnDelete();

            // Remarks
            $table->text('remarks_for_disinfection')->nullable(); 
 
            // Photo references (stored as JSON array of Photo IDs)
            $table->json('photo_ids')->nullable();
 
            // Guards from users table
            $table->foreignId('hatchery_guard_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('received_guard_id')->nullable()->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
 
            // Status 
            $table->tinyInteger('status')->default(0); 
            $table->timestamp('completed_at')->nullable(); 
            
            $table->softDeletes(); 
            $table->timestamps(); 
        }); 
    } 
 
    public function down(): void 
    { 
        Schema::dropIfExists('disinfection_slips'); 
    } 
};