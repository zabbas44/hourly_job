<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workers', function (Blueprint $table): void {
            $table->string('phone', 50)->nullable()->change();
            $table->string('email')->nullable()->change();
            $table->string('bank_title')->nullable()->change();
            $table->string('account_number')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('workers', function (Blueprint $table): void {
            $table->string('phone', 50)->nullable(false)->change();
            $table->string('email')->nullable(false)->change();
            $table->string('bank_title')->nullable(false)->change();
            $table->string('account_number')->nullable(false)->change();
        });
    }
};
