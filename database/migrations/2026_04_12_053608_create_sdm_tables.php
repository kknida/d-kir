<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // 1. Jabatan
        Schema::create('jabatans', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });

        // 2. Penanggung Jawab Ruangan
        Schema::create('penanggung_jawabs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jabatan_id')->constrained('jabatans');
            $table->string('nama');
            $table->string('nip')->unique();
            $table->string('kontak')->nullable();
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });

        // 3. User (Update/Create)
        // Jika project baru, gunakan ini. Jika sudah ada tabel user bawaan Laravel, gunakan table() bukan create()
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cabang_id')->nullable()->constrained('cabangs');
            $table->string('nama');
            $table->string('user');
            $table->string('password');
            $table->enum('role', ['admin_pusat', 'admin_cabang', 'viewer'])->default('viewer');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sdm_tables');
    }
};
