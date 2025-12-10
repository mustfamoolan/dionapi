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
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->string('name');
            $table->string('sku'); // SKU code - unique per client
            $table->decimal('purchase_price', 10, 2); // سعر الشراء
            $table->decimal('wholesale_price', 10, 2); // سعر البيع جملة
            $table->decimal('retail_price', 10, 2); // سعر البيع مفرد
            $table->enum('unit_type', ['weight', 'piece', 'carton']); // نوع الوحدة
            $table->decimal('weight', 10, 3)->nullable(); // وزن المنتج (إذا unit_type = weight)
            $table->enum('weight_unit', ['kg', 'g'])->nullable(); // وحدة الوزن
            $table->integer('pieces_per_carton')->nullable(); // عدد القطع في الكارتون (إذا unit_type = carton)
            $table->decimal('piece_price_in_carton', 10, 2)->nullable(); // سعر القطعة داخل الكارتون
            $table->decimal('total_quantity', 10, 2)->default(0); // العدد الكلي
            $table->decimal('remaining_quantity', 10, 2)->default(0); // العدد المتبقي
            $table->decimal('min_quantity', 10, 2)->default(0); // الحد الأدنى للتنبيه
            $table->boolean('is_low_stock')->default(false); // هل الكمية منخفضة
            $table->timestamps();

            // SKU must be unique per client
            $table->unique(['client_id', 'sku']);
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
