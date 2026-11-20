<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('amount', 12, 2);
            $table->string('frequency', 20); // one_time, monthly, per_batch
            $table->string('applies_to', 30); // batch, member_contribution
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('due_day')->nullable();
            $table->unsignedSmallInteger('due_in_days')->default(14);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('import_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type', 30)->default('students');
            $table->json('column_mapping');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('imports', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->string('type', 30)->default('students');
            $table->string('file');
            $table->string('original_filename');
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();
            $table->json('column_mapping')->nullable();
            $table->string('duplicate_rule', 30)->default('skip');
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('created_count')->default(0);
            $table->unsignedInteger('updated_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->string('status', 20)->default('pending');
            $table->json('failures')->nullable();
            $table->string('error_report_file')->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imports');
        Schema::dropIfExists('import_templates');
        Schema::dropIfExists('fee_plans');
    }
};
