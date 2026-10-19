<?php

namespace App\Services;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TaskService
{
    public function __construct(private VisibilityService $visibility) {}

    public function create(User $actor, array $data): Task
    {
        return DB::transaction(function () use ($actor, $data) {
            // Retried creates with the same client_uuid return the original record.
            if ($existing = Task::where('client_uuid', $data['client_uuid'])->first()) {
                return $existing;
            }
            $task = new Task($data);
            $task->creator_id = $actor->id;
            $task->status = TaskStatus::Pending;
            $task->review_status = 'not_required';
            $task->save();
            $this->log($task, $actor, 'created', null, 'pending');

            return $task;
        });
    }

    public function transition(User $actor, Task $task, TaskStatus $next, ?int $expectedVersion = null, ?string $notes = null): Task
    {
        return DB::transaction(function () use ($actor, $task, $next, $expectedVersion, $notes) {
            $task = Task::whereKey($task->id)->lockForUpdate()->firstOrFail();

            if ($expectedVersion !== null && $expectedVersion !== $task->version) {
                throw ValidationException::withMessages(['version' => 'Task was changed by someone else (conflict).']);
            }
            $from = $task->status;
            if (! $from->canTransitionTo($next)) {
                throw ValidationException::withMessages(['status' => "Cannot change status from {$from->value} to {$next->value}."]);
            }
            // Reopening a completed task is limited to those who can manage tasks in the office.
            if ($from === TaskStatus::Completed && ! $actor->hasPermissionInOffice('task.manage', $task->responsible_office_id)) {
                throw ValidationException::withMessages(['status' => 'Only an administrator can reopen a completed task.']);
            }

            $task->status = $next;
            if ($next === TaskStatus::Completed) {
                $task->completed_at = now();
                $task->completion_notes = $notes;
                $task->review_status = $task->requires_review ? 'awaiting' : 'not_required';
            } elseif ($from === TaskStatus::Completed) {
                $task->completed_at = null;
                $task->review_status = $task->requires_review ? 'awaiting' : 'not_required';
            }
            $task->version++;
            $task->save();
            $this->log($task, $actor, 'status_changed', $from->value, $next->value, $notes);

            return $task;
        });
    }

    /** Only the designated reviewer (or a task manager in the office) may verify; assignees never self-verify. */
    public function review(User $actor, Task $task, bool $approve): Task
    {
        if (! $task->requires_review || $task->review_status !== 'awaiting') {
            throw ValidationException::withMessages(['review' => 'Task is not awaiting review.']);
        }
        if ($actor->id === $task->assignee_id) {
            throw ValidationException::withMessages(['review' => 'Assignees cannot verify their own task.']);
        }

        return DB::transaction(function () use ($actor, $task, $approve) {
            $task->review_status = $approve ? 'verified' : 'rejected';
            if (! $approve) {
                $task->status = TaskStatus::InProgress;
                $task->completed_at = null;
            }
            $task->version++;
            $task->save();
            $this->log($task, $actor, 'review', 'awaiting', $task->review_status);

            return $task;
        });
    }

    public function reassign(User $actor, Task $task, ?int $assigneeId): Task
    {
        return DB::transaction(function () use ($actor, $task, $assigneeId) {
            $from = $task->assignee_id;
            $task->assignee_id = $assigneeId;
            $task->version++;
            $task->save();
            $this->log($task, $actor, 'reassigned', (string) $from, (string) $assigneeId);

            return $task;
        });
    }

    private function log(Task $task, User $actor, string $type, ?string $from, ?string $to, ?string $body = null): void
    {
        TaskActivity::create([
            'task_id' => $task->id, 'user_id' => $actor->id, 'type' => $type,
            'from_value' => $from, 'to_value' => $to, 'body' => $body,
        ]);
    }
}
