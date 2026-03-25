<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metal_pools', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('metal_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('total_quantity_kg', 18, 4)->default(0);
            $table->decimal('total_units', 24, 8)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metal_pools');
    }
};
