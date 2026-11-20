<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $table->string('full_name');
            $table->string('gender', 10)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->boolean('is_minor')->default(false);
            $table->string('phone', 20)->index();
            $table->string('alt_phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('sub_city', 100)->nullable();
            $table->string('address')->nullable();
            $table->string('school')->nullable();
            $table->string('grade_or_year', 50)->nullable();
            $table->string('guardian_name')->nullable();
            $table->string('guardian_phone', 20)->nullable();
            $table->string('guardian_relation', 50)->nullable();
            $table->boolean('guardian_consent')->default(false);
            $table->timestamp('consent_at')->nullable();
            $table->boolean('photo_consent')->default(false);
            $table->text('notes')->nullable();
            $table->string('locale', 5)->default('am');
            $table->string('status', 20)->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->string('telegram_chat_id', 32)->nullable();
            $table->timestamp('telegram_linked_at')->nullable();
            $table->timestamp('telegram_blocked_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
