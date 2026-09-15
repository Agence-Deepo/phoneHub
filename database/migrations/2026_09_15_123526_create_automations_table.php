<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phone_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->string('status')->default('pending'); // pending, running, success, failed, cancelled
            $table->string('geelark_task_id')->nullable();
            $table->json('config')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automations');
    }
};
