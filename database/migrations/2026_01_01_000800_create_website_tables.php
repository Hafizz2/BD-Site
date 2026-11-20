<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('key', 40)->nullable()->unique(); // system pages: about, services, articles, ...
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('title_am')->nullable();
            $table->string('eyebrow')->nullable();
            $table->string('eyebrow_am')->nullable();
            $table->text('intro')->nullable();
            $table->text('intro_am')->nullable();
            $table->longText('body')->nullable();
            $table->longText('body_am')->nullable();
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 300)->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('title_am')->nullable();
            $table->string('slug')->unique();
            $table->string('summary', 500)->nullable();
            $table->string('summary_am', 500)->nullable();
            $table->longText('body')->nullable();
            $table->longText('body_am')->nullable();
            $table->string('icon', 60)->default('graduation');
            $table->string('tag')->nullable();
            $table->string('tag_am')->nullable();
            $table->foreignId('program_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('title_am')->nullable();
            $table->text('description')->nullable();
            $table->text('description_am')->nullable();
            $table->string('icon', 60)->default('target');
            $table->string('horizon')->nullable(); // e.g. "2026", "Long term"
            $table->string('horizon_am')->nullable();
            $table->unsignedTinyInteger('progress')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('article_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_am')->nullable();
            $table->string('slug')->unique();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('members')->nullOnDelete();
            $table->string('author_name')->nullable();
            $table->string('title');
            $table->string('title_am')->nullable();
            $table->string('slug')->unique();
            $table->string('excerpt', 500)->nullable();
            $table->string('excerpt_am', 500)->nullable();
            $table->longText('body')->nullable();
            $table->longText('body_am')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('views')->default(0);
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 300)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'published_at']);
        });

        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('title_am')->nullable();
            $table->string('slug')->unique();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->string('location')->nullable();
            $table->string('location_am')->nullable();
            $table->string('summary', 500)->nullable();
            $table->string('summary_am', 500)->nullable();
            $table->longText('description')->nullable();
            $table->longText('description_am')->nullable();
            $table->string('registration_url')->nullable();
            $table->foreignId('program_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('gallery_albums', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('title_am')->nullable();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('description_am')->nullable();
            $table->date('taken_on')->nullable();
            $table->foreignId('program_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('contains_minors')->default(false);
            $table->boolean('consent_confirmed')->default(false);
            $table->foreignId('consent_confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_published')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('role')->nullable();
            $table->string('role_am')->nullable();
            $table->text('quote');
            $table->text('quote_am')->nullable();
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('question');
            $table->string('question_am')->nullable();
            $table->text('answer');
            $table->text('answer_am')->nullable();
            $table->string('category')->nullable();
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('stats', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->string('label_am')->nullable();
            $table->unsignedInteger('value')->default(0);
            $table->string('suffix', 10)->nullable();
            $table->string('auto_source', 30)->nullable(); // students, batches, members, programs
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('journey_steps', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('title_am')->nullable();
            $table->text('description')->nullable();
            $table->text('description_am')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('subject')->nullable();
            $table->text('message');
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('read_at')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['contact_messages', 'journey_steps', 'stats', 'faqs', 'testimonials', 'gallery_albums', 'events', 'articles', 'article_categories', 'goals', 'services', 'pages'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
