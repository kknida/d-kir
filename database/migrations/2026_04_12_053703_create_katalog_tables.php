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
        // 1. Kategori
        Schema::create('kategoris', function (Blueprint $table) {
            $table->id();
            $table->string('nama'); // Misal: Elektronik, Mebeul
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });

        // 2. Tipe
        Schema::create('tipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kategori_id')->constrained('kategoris');
            $table->string('nama'); // Misal: Laptop, Meja Kerja
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });

        // 3. Brand
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('nama'); // Misal: Dell, IKEA, Samsung
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('katalog_tables');
    }
};
