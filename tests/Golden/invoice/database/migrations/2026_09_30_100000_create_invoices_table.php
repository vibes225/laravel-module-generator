<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->string('status')->default('draft');
            $table->decimal('amount', 10, 2);
            $table->date('issued_at');
            $table->string('attachment')->nullable();
            $table->boolean('is_paid')->default(false);
            $table->string('secret');
            $table->json('meta')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('client_id')->constrained('clients')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
