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
        // Hapus tabel forecasts yang tidak digunakan secara persisten
        Schema::dropIfExists('forecasts');

        // Sederhanakan tabel products
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['sku', 'price', 'description']);
        });

        // Sederhanakan tabel sales
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['unit_price', 'total', 'notes']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Schema::create('forecasts', ...);
    }
};
