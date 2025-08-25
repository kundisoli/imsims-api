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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('sku')->unique();
            $table->string('barcode')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->decimal('cost_price', 10, 2)->default(0);
            $table->decimal('selling_price', 10, 2)->default(0);
            $table->integer('min_stock_level')->default(0);
            $table->integer('max_stock_level')->default(0);
            $table->integer('reorder_point')->default(0);
            $table->string('unit_of_measure')->default('piece');
            $table->decimal('weight', 8, 2)->nullable();
            $table->json('dimensions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_trackable')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('set null');
            
            $table->index(['is_active', 'is_trackable']);
            $table->index('sku');
            $table->index('barcode');
            $table->index('category_id');
            $table->index('supplier_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};