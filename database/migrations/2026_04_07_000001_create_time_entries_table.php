<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('worker_id')->constrained()->cascadeOnDelete();
            $table->date('work_date');
            $table->unsignedTinyInteger('hours');
            $table->timestamps();

            $table->unique(['worker_id', 'work_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_entries');
    }
};
