<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('publishing_office_id')->constrained('offices')->restrictOnDelete();
            $t->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $t->string('title');
            $t->text('body');
            $t->enum('priority', ['normal', 'important', 'urgent'])->default('normal');
            $t->boolean('requires_acknowledgment')->default(false);
            $t->timestamp('published_at')->nullable()->index();
            $t->timestamp('expires_at')->nullable();
            $t->timestamps();
        });

        Schema::create('announcement_audiences', function (Blueprint $t) {
            $t->foreignId('announcement_id')->constrained()->cascadeOnDelete();
            $t->foreignId('office_id')->constrained()->cascadeOnDelete();
            $t->primary(['announcement_id', 'office_id']);
        });

        Schema::create('announcement_reads', function (Blueprint $t) {
            $t->foreignId('announcement_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->timestamp('read_at')->nullable();
            $t->timestamp('acknowledged_at')->nullable();
            $t->primary(['announcement_id', 'user_id']);
        });

        Schema::create('app_notifications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('type', 48);
            $t->string('dedupe_key', 120);           // prevents duplicate notifications
            $t->string('title');
            $t->string('body', 500)->nullable();     // keep short; no confidential content
            $t->string('subject_type', 48)->nullable();
            $t->unsignedBigInteger('subject_id')->nullable();
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
            $t->unique(['user_id', 'dedupe_key']);
            $t->index(['user_id', 'read_at']);
        });

        // Metadata only; files live on a private disk and are served through an authorized endpoint.
        Schema::create('attachments', function (Blueprint $t) {
            $t->id();
            $t->string('attachable_type', 48);
            $t->unsignedBigInteger('attachable_id');
            $t->foreignId('office_id')->constrained()->restrictOnDelete();
            $t->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $t->string('original_name');
            $t->string('storage_path');
            $t->string('mime_type', 100);
            $t->unsignedBigInteger('size_bytes');
            $t->char('sha256', 64);
            $t->timestamps();
            $t->index(['attachable_type', 'attachable_id']);
        });

        Schema::create('sync_states', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('device_id', 64);
            $t->timestamp('last_pulled_at')->nullable();
            $t->timestamps();
            $t->unique(['user_id', 'device_id']);
        });
    }

    public function down(): void
    {
        foreach (['sync_states', 'attachments', 'app_notifications', 'announcement_reads',
                  'announcement_audiences', 'announcements'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
