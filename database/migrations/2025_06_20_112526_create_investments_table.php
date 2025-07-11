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
        Schema::create('investments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');

            $table->string('name'); // Nama investasi (contoh: Beli Motor, Saham ABC)
            $table->enum('type', ['goal', 'stock']); // Jenis investasi

            // Untuk type 'goal'
            $table->unsignedBigInteger('target_amount')->nullable(); // Target nominal yang ingin dicapai
            $table->date('due_date')->nullable(); // Tanggal target tercapai

            // Untuk type 'stock'
            $table->unsignedBigInteger('purchase_amount')->nullable(); // Modal awal beli saham
            $table->unsignedBigInteger('current_value')->nullable();   // Nilai sekarang saham
            $table->unsignedBigInteger('expected_return')->nullable();  // ROI tahunan (%) (opsional)

            // Umum
            $table->unsignedBigInteger('saved_amount')->default(0); // Tabungan saat ini (goal), atau modal awal (stock)
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investments');
    }
};
