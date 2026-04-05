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
        Schema::table('returned_inventory', function (Blueprint $table) {
             $table->enum('status', ['pending', 'approved', 'merged'])
                  ->default('pending')
                  ->after('unit'); // adjust position if needed
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('returned_inventory', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
