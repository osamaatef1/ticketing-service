<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->json('name')->nullable();
            $table->boolean('status')->default(true);
            $table->boolean('is_custom')->default(false);
            $table->boolean('enable_email')->default(true);
            $table->foreignId('zone_id')
                ->nullable()
                ->constrained('zones')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('parent_id')
                ->references('id')->on('categories')
                ->nullOnDelete();

            $table->index('status');
            $table->index('parent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
