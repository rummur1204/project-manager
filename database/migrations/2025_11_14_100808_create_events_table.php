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
       Schema::create('events', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->text('description')->nullable();
        $table->date('start_date');
        $table->date('end_date')->nullable();
        $table->foreignId('project_id')->nullable()->constrained()->onDelete('cascade'); // optional link to project
        $table->foreignId('task_id')->nullable()->constrained('tasks')->onDelete('cascade'); // optional link to a task event
        $table->unsignedBigInteger('created_by')->nullable();
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
