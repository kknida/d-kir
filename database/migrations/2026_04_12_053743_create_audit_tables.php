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
        // 1. Mutasi Barang (Histori Perpindahan)
        Schema::create('mutasi_barangs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barang_id')->constrained('barangs');
            $table->foreignId('ruangan_asal_id')->constrained('ruangans');
            $table->foreignId('ruangan_tujuan_id')->constrained('ruangans');
            $table->foreignId('user_id')->constrained('users'); // Siapa yang memindahkan
            $table->date('tanggal_mutasi');
            $table->text('alasan_mutasi')->nullable();
            $table->timestamps();
        });

        // 2. Histori Kondisi (Audit Trail Kondisi)
        Schema::create('kondisi_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kir_id')->constrained('kirs')->onDelete('cascade');
            $table->enum('kondisi_lama', ['Baik', 'Rusak Ringan', 'Rusak Berat']);
            $table->enum('kondisi_baru', ['Baik', 'Rusak Ringan', 'Rusak Berat']);
            $table->string('foto_bukti')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });

        // 3. Log Aktivitas (Sesuai permintaan log_datakir)
        Schema::create('log_aktivitas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('aksi'); // Misal: "Tambah Barang", "Update Ruangan"
            $table->string('model_terkait')->nullable(); // Nama Tabel
            $table->unsignedBigInteger('id_terkait')->nullable(); // ID baris yang diubah
            $table->json('payload')->nullable(); // Menyimpan data sebelum/sesudah (Opsional)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_tables');
    }
};
