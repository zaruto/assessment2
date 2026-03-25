<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('reference_number')->unique();
            $table->unsignedInteger('sequence_number')->unique();
            $table->foreignUlid('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('metal_id')->constrained()->cascadeOnDelete();
            $table->enum('storage_type', ['Allocated', 'Unallocated']);
            $table->decimal('quantity_kg', 18, 4);
            $table->decimal('price_per_kg_snapshot', 15, 2);
            $table->decimal('value_snapshot', 18, 2);
            $table->timestamp('withdrawn_at');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
    }
};
