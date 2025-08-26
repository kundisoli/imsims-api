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
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('location')->default('main');
            $table->integer('quantity')->default(0);
            $table->integer('reserved_quantity')->default(0);
            $table->integer('minimum_quantity')->default(0);
            $table->integer('maximum_quantity')->default(1000);
            $table->string('batch_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->decimal('cost_per_unit', 15, 2);
            $table->timestamps();

            $table->index(['product_id', 'location']);
            $table->index('batch_number');
            $table->index('expiry_date');
            $table->unique(['product_id', 'location', 'batch_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
