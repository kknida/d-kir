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
        Schema::table('sap_assets', function (Blueprint $table) {
            // Menambahkan kolom deskripsi kedua setelah asset_description
            $table->text('asset_description_2')->nullable()->after('is_locked');
        });
    }

    public function down(): void
    {
        Schema::table('sap_assets', function (Blueprint $table) {
            $table->dropColumn('asset_description_2');
        });
    }
};
