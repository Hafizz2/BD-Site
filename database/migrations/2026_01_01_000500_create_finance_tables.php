<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dues', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->morphs('payable');
            $table->foreignId('fee_plan_id')->constrained()->cascadeOnDelete();
            $table->string('period', 30);
            $table->string('description')->nullable();
            $table->decimal('amount', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->date('due_date');
            $table->string('status', 20)->default('unpaid');
            $table->foreignId('waived_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('waive_reason')->nullable();
            $table->timestamp('reminder_before_sent_at')->nullable();
            $table->timestamp('reminder_due_sent_at')->nullable();
            $table->unsignedTinyInteger('overdue_reminders_count')->default(0);
            $table->timestamp('last_overdue_reminder_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['payable_type', 'payable_id', 'fee_plan_id', 'period'], 'dues_unique_period');
            $table->index(['status', 'due_date']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('due_id')->nullable()->constrained('dues')->nullOnDelete();
            $table->morphs('payable');
            $table->decimal('amount', 12, 2);
            $table->string('method', 20);
            $table->string('transaction_ref', 100)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('status', 20)->default('pending_review');
            $table->string('submitted_via', 20)->default('admin');
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->string('finance_message_id', 32)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'paid_at']);
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('category', 30);
            $table->decimal('amount', 12, 2);
            $table->date('spent_at');
            $table->string('description');
            $table->foreignId('program_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->string('donor_name')->nullable();
            $table->boolean('is_anonymous')->default(false);
            $table->string('donor_phone', 20)->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('method', 20);
            $table->string('transaction_ref', 100)->nullable();
            $table->string('purpose')->nullable();
            $table->foreignId('program_id')->nullable()->constrained()->nullOnDelete();
            $table->date('received_at');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donations');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('dues');
    }
};
