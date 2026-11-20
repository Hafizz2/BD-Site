<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->string('full_name');
            $table->string('gender', 10)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('phone', 20)->nullable()->index();
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
            $table->string('status', 20)->default('active');
            $table->string('source', 20)->default('manual');
            $table->foreignId('import_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('registered_at')->nullable();
            $table->string('locale', 5)->default('am');
            $table->string('telegram_chat_id', 32)->nullable()->index();
            $table->timestamp('telegram_linked_at')->nullable();
            $table->timestamp('telegram_blocked_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $table->timestamp('enrolled_at')->nullable();
            $table->string('status', 20)->default('pending');
            $table->foreignId('import_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('certificate_issued_at')->nullable();
            $table->string('certificate_number', 40)->nullable()->unique();
            $table->timestamps();

            $table->unique(['student_id', 'batch_id']);
        });

        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->string('full_name');
            $table->string('full_name_am')->nullable();
            $table->string('phone', 20)->nullable()->index();
            $table->string('email')->nullable();
            $table->string('role_title')->nullable();
            $table->string('role_title_am')->nullable();
            $table->string('team', 30)->nullable();
            $table->text('bio')->nullable();
            $table->text('bio_am')->nullable();
            $table->boolean('show_on_website')->default(false);
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('monthly_contribution_plan_id')->nullable()->constrained('fee_plans')->nullOnDelete();
            $table->string('locale', 5)->default('am');
            $table->string('telegram_chat_id', 32)->nullable()->index();
            $table->timestamp('telegram_linked_at')->nullable();
            $table->timestamp('telegram_blocked_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
        Schema::dropIfExists('enrollments');
        Schema::dropIfExists('students');
    }
};
