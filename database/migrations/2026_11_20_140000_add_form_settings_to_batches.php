<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Google-Forms-style header for a batch's registration form: cover, title, opening note, thank-you message. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->json('form_settings')->nullable()->after('form_fields');
        });
    }

    public function down(): void
    {
        Schema::table('batches', fn (Blueprint $table) => $table->dropColumn('form_settings'));
    }
};
