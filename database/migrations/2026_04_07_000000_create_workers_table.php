<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workers', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('phone', 50);
            $table->string('email')->unique();
            $table->string('bank_title');
            $table->string('account_number');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workers');
    }
};
