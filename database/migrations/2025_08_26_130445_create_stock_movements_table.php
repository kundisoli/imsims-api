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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('stock_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['in', 'out', 'adjustment', 'transfer']);
            $table->integer('quantity');
            $table->enum('reason', ['purchase', 'sale', 'return', 'damaged', 'expired', 'lost', 'found', 'transfer', 'adjustment']);
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('cost_per_unit', 15, 2)->nullable();
            $table->decimal('total_cost', 15, 2)->nullable();
            $table->string('location_from')->nullable();
            $table->string('location_to')->nullable();
            $table->timestamp('performed_at');
            $table->timestamps();

            $table->index(['product_id', 'type']);
            $table->index('performed_at');
            $table->index('reference_number');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
