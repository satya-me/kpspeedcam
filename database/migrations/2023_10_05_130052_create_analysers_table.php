<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('analysers', function (Blueprint $table) {
            $table->id();
            $table->string('ticket');
            $table->string('ticket_number');
            $table->string('license_number')->nullable();
            $table->string('speedLimit_kph');
            $table->string('speedTrigger_kph');
            $table->string('calculatedSpeed_kph');
            $table->string('targetSpeed_kph');
            $table->string('latitude');
            $table->string('longitude');
            $table->string('capture_at');
            $table->string('type');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analysers');
    }
};
