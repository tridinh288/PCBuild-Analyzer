<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('build_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('build_id')->constrained()->cascadeOnDelete();
            // RESTRICT: a product used by a template cannot be deleted, only deactivated (D-007)
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('quantity')->default(1); // RAM: kits, storage: identical drives
            $table->timestamps();

            $table->unique(['build_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('build_items');
    }
};
