<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('name', 150);
            $table->string('slug', 180)->unique();
            $table->string('brand', 60)->index();
            $table->string('model', 100);
            $table->unsignedBigInteger('price'); // integer VND (D-010)
            $table->string('image_public_id')->nullable(); // Cloudinary public_id (D-022)
            $table->text('description')->nullable();
            $table->json('specs'); // keys defined in config/hardware.php (D-004)
            $table->boolean('is_active')->default(true); // deactivate instead of delete (D-007)
            $table->timestamps();

            $table->index(['category_id', 'price']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
