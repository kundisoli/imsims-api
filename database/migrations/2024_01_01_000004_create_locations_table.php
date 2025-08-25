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
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warehouse_id');
            $table->string('name');
            $table->string('code')->unique();
            $table->string('type')->nullable(); // shelf, bin, rack, etc.
            $table->string('aisle')->nullable();
            $table->string('rack')->nullable();
            $table->string('shelf')->nullable();
            $table->string('bin')->nullable();
            $table->string('barcode')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('capacity')->nullable();
            $table->boolean('temperature_controlled')->default(false);
            $table->boolean('hazardous_materials')->default(false);
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            $table->index(['warehouse_id', 'is_active']);
            $table->index('code');
            $table->index('barcode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};