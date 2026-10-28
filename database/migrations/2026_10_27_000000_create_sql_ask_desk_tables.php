<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('sku')->unique();
            $table->string('name');
            $table->string('category');
            $table->decimal('unit_price', 10, 2);
            $table->unsignedInteger('stock_quantity');
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->string('order_number')->unique();
            $table->date('ordered_at');
            $table->string('customer_name');
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->timestamps();
        });

        Schema::create('ask_queries', function (Blueprint $table): void {
            $table->id();
            $table->text('question');
            $table->text('sql_text')->nullable();
            $table->unsignedInteger('row_count')->default(0);
            $table->string('status')->default('failed');
            $table->json('preview_json')->nullable();
            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('ask_queries');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('products');
    }
};
