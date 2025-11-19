<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('activity_type_id')->constrained('activity_types')->onDelete('cascade');
            $table->foreignId('project_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->date('due_date')->nullable();
            $table->enum('status', ['Pending','In Progress','Completed'])->default('Pending');
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('activities');
    }
};
