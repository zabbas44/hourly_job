<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workers', function (Blueprint $table): void {
            $table->decimal('hourly_rate', 10, 2)->default(0)->after('account_number');
        });
    }

    public function down(): void
    {
        Schema::table('workers', function (Blueprint $table): void {
            $table->dropColumn('hourly_rate');
        });
    }
};
