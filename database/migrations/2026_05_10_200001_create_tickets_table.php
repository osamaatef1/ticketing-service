<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique();
            $table->string('sequence_number', 5)->default('00000');

            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('country_code', 8)->nullable();
            $table->string('phone', 32)->nullable();

            $table->string('subject');
            $table->longText('description');

            $table->string('status', 32)->default('new');
            $table->string('platform', 32)->nullable();
            $table->string('token', 64)->nullable();

            $table->dateTime('occurrence_time')->nullable();
            $table->boolean('is_valid')->default(true);

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->unsignedBigInteger('ticket_category_id')->nullable();
            $table->foreignId('category_id')
                ->nullable()
                ->constrained('categories')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('sub_category_id')->nullable();
            $table->unsignedBigInteger('sub_sub_category_id')->nullable();
            $table->unsignedBigInteger('type_id')->nullable();
            $table->unsignedBigInteger('season_id')->nullable();
            $table->unsignedBigInteger('zone_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('admin_id');
            $table->index('created_by');
            $table->index('category_id');
            $table->index('created_at');
            $table->index(['season_id', 'zone_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
