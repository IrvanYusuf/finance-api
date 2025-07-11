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
        Schema::table('transactions', function (Blueprint $table) {
            // Hapus foreign key dulu
            $table->dropForeign(['source_id']);

            // Ubah kolom jadi nullable
            $table->uuid('source_id')->nullable()->change();

            // Tambahkan foreign key kembali
            $table->foreign('source_id')
                ->references('id')
                ->on('sources')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['source_id']);
            $table->uuid('source_id')->nullable(false)->change(); // kembalikan jadi tidak nullable
            $table->foreign('source_id')
                ->references('id')
                ->on('sources')
                ->onDelete('cascade');
        });
    }
};
