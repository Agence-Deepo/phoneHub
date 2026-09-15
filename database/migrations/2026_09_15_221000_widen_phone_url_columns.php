<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('phones', function (Blueprint $table) {
            $table->text('remote_url')->nullable()->change();
            $table->text('screenshot_url')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('phones', function (Blueprint $table) {
            $table->string('remote_url')->nullable()->change();
            $table->string('screenshot_url')->nullable()->change();
        });
    }
};
