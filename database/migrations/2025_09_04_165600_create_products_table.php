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
            // $table->string('payment_id')->unique();
            $table->string('name'); // Stripe’s payment intent ID
            $table->decimal('price', 50, 2)->default(0);
            $table->integer('is_featured')->default(0);
            $table->text('featured_text')->nullable->default(null);
            $table->decimal('total_revenue', 50, 2)->default(0);
            $table->integer('quantity')->default(0);
            $table->enum('status', ['pending', 'succeeded', 'failed','deleted','blocked'])->default('pending');
            $table->timestamps();
            $table->integer('num_purchased')->default(0);
            $table->json('media')->nullable();
            $table->json('review_sumary')->nullable();
            $table->string('review')->nullable();
            $table->integer('total_reviews')->nullable();
            $table->integer('num_review')->nullable();
            $table->longText('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users');

            

            
        });


        Schema::create('variant_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('variant_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('variant_type_id');
            $table->string('value');
            $table->foreign('variant_type_id')->references('id')->on('variant_types');
            $table->timestamps();
        });


        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->integer('quantity')->default(0);
            $table->unsignedBigInteger('variant_value_id');
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('variant_value_id')->references('id')->on('variant_values')->onDelete('cascade');
            $table->timestamps();
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
