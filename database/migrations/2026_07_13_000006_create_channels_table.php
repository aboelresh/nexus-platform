<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->enum('type', ['text', 'voice', 'announcement', 'readonly'])->default('text');
            $table->unsignedInteger('position')->default(0);
            $table->string('category')->nullable();
            $table->boolean('is_private')->default(false);
            $table->json('permissions')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['group_id', 'slug']);
            $table->index(['group_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channels');
    }
};