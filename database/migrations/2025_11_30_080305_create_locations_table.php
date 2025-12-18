<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('location_name');

            // consistent attachment reference
            $table->foreignId('attachment_id')
                  ->nullable()
                  ->constrained('attachments')
                  ->cascadeOnUpdate()
                  ->nullOnDelete();

            $table->boolean('disabled')->default(false);
            $table->timestamps();
            $table->softDeletes(); 

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
