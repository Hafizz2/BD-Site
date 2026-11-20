<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_am')->nullable();
            $table->string('slug')->unique();
            $table->string('type', 30)->default('other');
            $table->string('summary', 500)->nullable();
            $table->string('summary_am', 500)->nullable();
            $table->longText('description')->nullable();
            $table->longText('description_am')->nullable();
            $table->string('icon', 60)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('show_on_website')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('season', 20)->default('regular');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->boolean('registration_open')->default(false);
            $table->string('status', 20)->default('planned');
            $table->string('location')->nullable();
            $table->string('schedule_note')->nullable();
            $table->string('schedule_note_am')->nullable();
            $table->timestamps();

            $table->index(['program_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batches');
        Schema::dropIfExists('programs');
    }
};
