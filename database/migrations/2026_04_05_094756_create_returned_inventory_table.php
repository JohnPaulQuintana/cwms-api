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
        Schema::create('returned_inventory', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inventory_request_id');
            $table->string('inventory_name');
            $table->unsignedBigInteger('project_id');
            $table->string('warehouse_name');
            $table->integer('quantity');
            $table->string('unit');
            $table->timestamps();

            // Optional: add foreign key if inventory_requests table exists
            $table->foreign('inventory_request_id')
                  ->references('id')->on('inventory_requests')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('returned_inventory');
    }
};
