<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phones', function (Blueprint $table) {
            $table->id();
            $table->string('geelark_id')->nullable()->unique();
            $table->string('name');
            $table->string('serial_no')->nullable();
            $table->string('status')->default('offline'); // online, starting, offline, error
            $table->string('country')->nullable();
            $table->string('group_name')->nullable();
            $table->json('tags')->nullable();
            $table->string('proxy')->nullable();
            $table->string('mobile_type')->nullable(); // Android 12, etc.
            $table->text('remote_url')->nullable();
            $table->text('screenshot_url')->nullable();
            $table->string('screenshot_task_id')->nullable();
            $table->text('remark')->nullable();
            $table->json('equipment_info')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phones');
    }
};
