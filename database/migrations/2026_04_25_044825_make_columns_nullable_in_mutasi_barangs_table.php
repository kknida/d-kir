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
        Schema::table('mutasi_barangs', function (Blueprint $table) {
            $table->foreignId('ruangan_asal_id')->nullable()->change();
            $table->foreignId('ruangan_tujuan_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mutasi_barangs', function (Blueprint $table) {
            //
        });
    }
};
