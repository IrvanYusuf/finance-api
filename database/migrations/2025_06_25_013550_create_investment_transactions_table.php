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
        Schema::create('investment_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('source_id');
            $table->uuid('investment_id');
            $table->unsignedBigInteger('amount');
            $table->date('transaction_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('investment_id')
                ->references('id')
                ->on('investments')
                ->onDelete('cascade');

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
        Schema::dropIfExists('investment_transactions');
    }
};
