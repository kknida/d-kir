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
        // 1. Cabang
        Schema::create('cabangs', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('keterangan')->nullable();
            $table->string('koordinat')->nullable();
            $table->timestamps();
        });

        // 2. Gedung
        Schema::create('gedungs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cabang_id')->constrained('cabangs')->onDelete('cascade');
            $table->string('nama');
            $table->text('alamat')->nullable();
            $table->string('koordinat')->nullable();
            $table->timestamps();
        });

        // 3. Lantai
        Schema::create('lantais', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gedung_id')->constrained('gedungs')->onDelete('cascade');
            $table->string('nama'); // Misal: Lantai 1, Rooftop, dll
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });

        // 4. Ruangan
        Schema::create('ruangans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lantai_id')->constrained('lantais')->onDelete('cascade');
            $table->string('nama');
            $table->string('kode_ruangan')->unique(); // Index untuk QR Code
            $table->string('qrcode_path')->nullable();
            $table->unsignedBigInteger('penanggung_jawab_id')->nullable(); // Nanti relasi ke PJ
            $table->timestamps();
            
            $table->index('kode_ruangan'); // Tambahan Index manual untuk performa
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wilayah_tables');
    }
};
