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
        Schema::table('activity_user', function (Blueprint $table) {
            // Simply drop the accepted column
            $table->dropColumn('accepted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_user', function (Blueprint $table) {
            // Re-add the column with the same definition
            $table->boolean('accepted')->default(false);
        });
    }
};