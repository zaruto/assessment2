<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('bars', 'withdrawal_id')) {
            Schema::table('bars', function (Blueprint $table): void {
                $table->foreignUlid('withdrawal_id')->nullable()->constrained()->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('bars', 'withdrawn_at')) {
            Schema::table('bars', function (Blueprint $table): void {
                $table->timestamp('withdrawn_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('bars', 'withdrawn_at')) {
            Schema::table('bars', function (Blueprint $table): void {
                $table->dropColumn('withdrawn_at');
            });
        }

        if (Schema::hasColumn('bars', 'withdrawal_id')) {
            Schema::table('bars', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('withdrawal_id');
            });
        }
    }
};
