<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('trucks', function (Blueprint $table) {
            $table->id();
            $table->string('plate_number')->unique();
            $table->boolean('disabled')->default(false);
            $table->timestamps();
            $table->softDeletes(); 

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trucks');
    }
};
