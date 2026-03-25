<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unallocated_positions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('metal_id')->constrained()->cascadeOnDelete();
            $table->decimal('units', 24, 8)->default(0);
            $table->timestamps();

            $table->unique(['customer_id', 'metal_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unallocated_positions');
    }
};
