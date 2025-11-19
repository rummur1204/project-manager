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
       Schema::create('calendar_events', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->text('description')->nullable();

        $table->date('start_date');
        $table->date('end_date')->nullable();

        $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
        $table->foreignId('task_id')->nullable()->constrained()->nullOnDelete();

        $table->foreignId('user_id')->constrained()->cascadeOnDelete();

        // event = normal event by user
        // project = auto-created from project deadline
        // task = user added deadline to task
        $table->enum('type', ['event', 'project', 'task'])->default('event');

        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};
