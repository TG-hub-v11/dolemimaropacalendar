<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Timestamps are stored in UTC. All-day events use start_date/end_date (no timezone).
        Schema::create('calendar_events', function (Blueprint $t) {
            $t->id();
            $t->uuid('client_uuid')->unique();           // dedupes retried creates
            $t->foreignId('owning_office_id')->constrained('offices')->restrictOnDelete();
            $t->foreignId('organizer_id')->constrained('users')->restrictOnDelete();
            $t->string('title');
            $t->text('description')->nullable();
            $t->string('category', 48)->default('general');
            $t->string('color', 9)->nullable();
            $t->boolean('is_all_day')->default(false);
            $t->dateTime('starts_at')->nullable();       // UTC
            $t->dateTime('ends_at')->nullable();         // UTC
            $t->date('start_date')->nullable();          // all-day
            $t->date('end_date')->nullable();            // all-day, inclusive
            $t->string('venue')->nullable();
            $t->string('online_link', 2048)->nullable();
            $t->enum('status', ['scheduled', 'updated', 'cancelled', 'completed'])->default('scheduled');
            $t->enum('visibility', ['office', 'participants', 'regional'])->default('office');
            $t->string('rrule')->nullable();             // RFC 5545 RRULE, evaluated in organization timezone
            $t->string('rrule_timezone', 64)->default('Asia/Manila');
            $t->json('reminder_offsets_minutes')->nullable();
            $t->unsignedInteger('version')->default(1);  // optimistic concurrency
            $t->timestamps();
            $t->softDeletes();
            $t->index(['owning_office_id', 'starts_at']);
            $t->index(['start_date', 'end_date']);
            $t->index('status');
        });

        Schema::create('event_offices', function (Blueprint $t) {
            $t->foreignId('calendar_event_id')->constrained()->cascadeOnDelete();
            $t->foreignId('office_id')->constrained()->cascadeOnDelete();
            $t->primary(['calendar_event_id', 'office_id']);
        });

        Schema::create('event_participants', function (Blueprint $t) {
            $t->foreignId('calendar_event_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->enum('response', ['pending', 'accepted', 'declined', 'tentative'])->default('pending');
            $t->timestamps();
            $t->primary(['calendar_event_id', 'user_id']);
        });

        // One row per modified/cancelled occurrence. Editing a single occurrence writes here
        // and never touches the parent rule, so other occurrences are unaffected.
        Schema::create('event_exceptions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('calendar_event_id')->constrained()->cascadeOnDelete();
            $t->dateTime('original_starts_at');          // UTC identity of the occurrence
            $t->boolean('is_cancelled')->default(false);
            $t->string('title')->nullable();
            $t->text('description')->nullable();
            $t->dateTime('starts_at')->nullable();
            $t->dateTime('ends_at')->nullable();
            $t->string('venue')->nullable();
            $t->timestamps();
            $t->unique(['calendar_event_id', 'original_starts_at']);
        });

        Schema::create('meetings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('calendar_event_id')->unique()->constrained()->cascadeOnDelete();
            $t->foreignId('office_id')->constrained()->restrictOnDelete();
            $t->enum('minutes_status', ['none', 'draft', 'finalized'])->default('none');
            $t->timestamps();
        });

        Schema::create('meeting_participants', function (Blueprint $t) {
            $t->id();
            $t->foreignId('meeting_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->enum('response', ['pending', 'accepted', 'declined', 'tentative'])->default('pending');
            $t->enum('attendance', ['unrecorded', 'present', 'absent', 'excused'])->default('unrecorded');
            $t->timestamps();
            $t->unique(['meeting_id', 'user_id']);
        });

        Schema::create('meeting_agenda_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('meeting_id')->constrained()->cascadeOnDelete();
            $t->unsignedSmallInteger('position');
            $t->string('title');
            $t->text('notes')->nullable();
            $t->timestamps();
        });

        Schema::create('meeting_minutes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('meeting_id')->unique()->constrained()->cascadeOnDelete();
            $t->longText('body');
            $t->text('decisions')->nullable();
            $t->enum('status', ['draft', 'finalized'])->default('draft');
            $t->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('finalized_at')->nullable();
            $t->unsignedInteger('version')->default(1);
            $t->timestamps();
        });

        Schema::create('meeting_minutes_revisions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('meeting_minutes_id')->constrained('meeting_minutes')->cascadeOnDelete();
            $t->unsignedInteger('version');
            $t->longText('body');
            $t->foreignId('edited_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('created_at')->useCurrent();
        });

        Schema::create('tasks', function (Blueprint $t) {
            $t->id();
            $t->uuid('client_uuid')->unique();
            $t->foreignId('responsible_office_id')->constrained('offices')->restrictOnDelete();
            $t->foreignId('creator_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('calendar_event_id')->nullable()->constrained()->nullOnDelete();
            // Meeting action items ARE tasks: no duplicated copies.
            $t->foreignId('meeting_id')->nullable()->constrained()->nullOnDelete();
            $t->string('title');
            $t->text('description')->nullable();
            $t->enum('priority', ['low', 'normal', 'high', 'critical'])->default('normal');
            $t->enum('status', ['pending', 'in_progress', 'blocked', 'completed', 'cancelled'])->default('pending');
            $t->boolean('requires_review')->default(false);
            $t->enum('review_status', ['not_required', 'awaiting', 'verified', 'rejected'])->default('not_required');
            $t->date('start_date')->nullable();
            $t->date('due_date')->nullable();
            $t->timestamp('completed_at')->nullable();
            $t->text('completion_notes')->nullable();
            $t->unsignedInteger('version')->default(1);
            $t->timestamps();
            $t->softDeletes();
            $t->index(['responsible_office_id', 'status']);
            $t->index(['assignee_id', 'status', 'due_date']);
            $t->index('due_date');
        });

        Schema::create('task_activities', function (Blueprint $t) {
            $t->id();
            $t->foreignId('task_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->enum('type', ['created', 'status_changed', 'reassigned', 'comment', 'progress', 'review']);
            $t->string('from_value')->nullable();
            $t->string('to_value')->nullable();
            $t->text('body')->nullable();
            $t->unsignedTinyInteger('progress_percent')->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['task_id', 'created_at']);
        });
    }

    public function down(): void
    {
        foreach (['task_activities', 'tasks', 'meeting_minutes_revisions', 'meeting_minutes',
                  'meeting_agenda_items', 'meeting_participants', 'meetings', 'event_exceptions',
                  'event_participants', 'event_offices', 'calendar_events'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
