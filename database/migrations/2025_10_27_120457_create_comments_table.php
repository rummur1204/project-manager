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
     Schema::create('comments', function (Blueprint $table) {
          $table->id();
          $table->foreignId('user_id')->constrained()->onDelete('cascade');
          $table->morphs('commentable'); // creates commentable_id (bigint) and commentable_type (string)
          $table->string('title');
          $table->text('message');
          $table->enum('urgency', ['Normal','High','Critical'])->default('Normal');
        //   $table->boolean('verified')->default(true); // optional: admin verification flag
          $table->timestamps();
       });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
