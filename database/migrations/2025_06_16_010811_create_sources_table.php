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
        Schema::create('sources', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name'); // Nama akun sumber: BCA, Dana, Cash
            $table->enum('type', ['cash', 'bank', 'ewallet']); // Jenis sumber

            $table->string('provider')->nullable(); // Nama bank atau nama ewallet (BCA, Mandiri, Dana, OVO)
            $table->string('account_number')->nullable(); // No rekening bank atau no HP ewallet
            $table->string('account_holder')->nullable(); // Nama pemilik akun

            $table->text('note')->nullable(); // Catatan tambahan
            $table->uuid('user_id');
            $table->string('color_card')->nullable();

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
        Schema::dropIfExists('sources');
    }
};
