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
        Schema::create('forecasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->decimal('alpha', 5, 4);
            $table->decimal('beta', 5, 4);
            $table->decimal('phi', 5, 4);
            $table->unsignedInteger('periods');
            $table->json('series');
            $table->decimal('level', 12, 4)->nullable();
            $table->decimal('trend', 12, 4)->nullable();
            $table->decimal('forecast', 12, 4);
            $table->string('method')->default('holt-damped');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forecasts');
    }
};
