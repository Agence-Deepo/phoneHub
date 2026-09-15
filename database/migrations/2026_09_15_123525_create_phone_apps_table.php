<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phone_apps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phone_id')->constrained()->cascadeOnDelete();
            $table->string('app_name');
            $table->string('package_name')->nullable();
            $table->string('app_version_id')->nullable();
            $table->string('status')->default('installed'); // pending, installed, failed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phone_apps');
    }
};
