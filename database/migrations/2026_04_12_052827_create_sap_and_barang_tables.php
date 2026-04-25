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
        // SAP KCL (Material/Stock)
        Schema::create('sap_kcls', function (Blueprint $table) {
            $table->id();
            // Kolom Utama
            $table->string('plant')->index();
            $table->string('material')->index(); // Ini SKU / Kode Barang
            $table->string('material_description')->nullable();
            $table->string('storage_location')->nullable();
            $table->string('batch')->nullable();
            
            // Data Kuantitas & Finansial
            $table->double('unrestricted')->default(0); // Stok yang bisa digunakan
            $table->string('currency', 10)->nullable();
            $table->string('name_1')->nullable(); // Biasanya nama vendor atau lokasi spesifik
            $table->string('material_type')->nullable();
            $table->string('material_group')->nullable();
            $table->double('value_unrestricted')->default(0); // Nilai uang dari stok
            
            // Keterangan Tambahan
            $table->string('descr_of_storage_loc')->nullable();
            $table->string('base_unit_of_measure', 10)->nullable(); // Misal: PC, Unit, Set
            
            $table->timestamps();
        });

        // SAP Asset
        Schema::create('sap_assets', function (Blueprint $table) {
            $table->id();
            // Identitas Asset
            $table->string('asset_number')->index(); // Asset Main Number
            $table->string('sub_number')->nullable();
            $table->string('asset_name')->nullable();
            $table->string('original_asset')->nullable();
            $table->text('asset_description')->nullable();
            $table->text('asset_main_no_text')->nullable();
            
            // Data Nilai & Akuntansi
            $table->double('acquis_val')->default(0); // Nilai Perolehan
            $table->double('accum_dep')->default(0); // Akumulasi Penyusutan
            $table->double('book_val')->default(0);  // Nilai Buku Saat Ini
            $table->double('quantity')->default(0);
            $table->string('base_unit_of_measure', 10)->nullable();
            
            // Masa Pakai & Klasifikasi
            $table->integer('useful_life')->nullable(); // Dalam tahun
            $table->integer('useful_life_in_periods')->nullable(); // Dalam bulan/periode
            $table->string('asset_class')->nullable();
            $table->date('capitalized_on')->nullable(); // Tanggal perolehan
            $table->boolean('is_locked')->default(false); // Locked to acquis
            $table->string('asset_location')->nullable();
            
            $table->timestamps();
        });

        // Tabel Barang Utama (Polymorphic)
        Schema::create('barangs', function (Blueprint $table) {
            $table->id();
            $table->string('kode_inventaris')->unique(); // QR Code Barang
            
            // Polymorphic Columns
            $table->nullableMorphs('sourceable'); 
            // Baris di atas otomatis membuat: sourceable_id dan sourceable_type
            
            $table->unsignedBigInteger('tipe_id')->nullable();
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->text('keterangan')->nullable();
            $table->string('foto_barang')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['sourceable_id', 'sourceable_type']); // Index untuk relasi poly
            $table->index('kode_inventaris');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sap_and_barang_tables');
    }
};
