<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bars', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('deposit_id')->constrained()->cascadeOnDelete();
            $table->string('serial_number')->unique();
            $table->decimal('weight_kg', 18, 4);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bars');
    }
};
