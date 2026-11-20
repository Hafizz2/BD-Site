<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-batch registration forms (editable fields + custom questions) and call tracking on registrations.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->json('form_fields')->nullable()->after('registration_open');
        });

        Schema::table('registrations', function (Blueprint $table) {
            $table->json('answers')->nullable()->after('notes');
            $table->timestamp('called_at')->nullable()->after('status');
            $table->string('call_note', 500)->nullable()->after('called_at');
        });
    }

    public function down(): void
    {
        Schema::table('batches', fn (Blueprint $table) => $table->dropColumn('form_fields'));
        Schema::table('registrations', fn (Blueprint $table) => $table->dropColumn(['answers', 'called_at', 'call_note']));
    }
};
