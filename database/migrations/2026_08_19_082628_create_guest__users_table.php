<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('guest_users', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique(); // ID unik untuk membedakan HP/Browser
            $table->string('name'); // Nama yang diinputkan user
            $table->string('current_location')->nullable(); // Menyimpan nama region/sub-region saat ini, null = idle
            $table->timestamp('last_seen_at')->nullable(); // Untuk mendeteksi apakah user masih online/aktif
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guest__users');
    }
};
