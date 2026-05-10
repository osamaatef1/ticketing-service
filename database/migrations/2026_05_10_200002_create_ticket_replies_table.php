<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->longText('description');
            $table->string('replied_by', 16);
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->boolean('send_email')->default(false);
            $table->boolean('send_whatsapp')->default(false);
            $table->boolean('send_sms')->default(false);
            $table->timestamps();

            $table->index('admin_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_replies');
    }
};
