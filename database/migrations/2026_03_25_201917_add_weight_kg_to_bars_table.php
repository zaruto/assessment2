<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('bars', 'weight_kg')) {
            Schema::table('bars', function (Blueprint $table) {
                $table->decimal('weight_kg', 18, 4)->default(0)->after('serial_number');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('bars', 'weight_kg')) {
            Schema::table('bars', function (Blueprint $table) {
                $table->dropColumn('weight_kg');
            });
        }
    }
};
