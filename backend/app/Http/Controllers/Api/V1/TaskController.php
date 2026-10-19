<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Services\TaskService;
use App\Services\VisibilityService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function __construct(private TaskService $tasks, private VisibilityService $visibility) {}

    public function index(Request $request)
    {
        $q = $this->visibility->scopeTasks(Task::query(), $request->user())
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->query('office_id'), fn ($q, $o) => $q->where('responsible_office_id', $o))
            ->when($request->boolean('mine'), fn ($q) => $q->where('assignee_id', $request->user()->id))
            ->when($request->boolean('overdue'), fn ($q) => $q->whereDate('due_date', '<', today())
                ->whereNotIn('status', ['completed', 'cancelled']))
            ->orderBy('due_date');

        return response()->json($q->paginate(min((int) $request->query('per_page', 25), 100))
            ->through(fn (Task $t) => $this->present($t)));
    }

    public function show(Request $request, Task $task)
    {
        $this->authorize('view', $task);

        return response()->json(['data' => $this->present($task)]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_uuid' => ['required', 'uuid'],
            'responsible_office_id' => ['required', 'exists:offices,id'],
            'assignee_id' => ['nullable', 'exists:users,id'],
            'reviewer_id' => ['nullable', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'priority' => ['sometimes', Rule::in(['low', 'normal', 'high', 'critical'])],
            'requires_review' => ['sometimes', 'boolean'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);
        $this->authorize('create', [Task::class, (int) $data['responsible_office_id']]);

        return response()->json(['data' => $this->present($this->tasks->create($request->user(), $data))], 201);
    }

    public function transition(Request $request, Task $task)
    {
        $this->authorize('updateStatus', $task);
        $data = $request->validate([
            'status' => ['required', Rule::enum(TaskStatus::class)],
            'version' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
        $updated = $this->tasks->transition($request->user(), $task, TaskStatus::from($data['status']), $data['version'] ?? null, $data['notes'] ?? null);

        return response()->json(['data' => $this->present($updated)]);
    }

    public function reassign(Request $request, Task $task)
    {
        $this->authorize('reassign', $task);
        $data = $request->validate(['assignee_id' => ['nullable', 'exists:users,id']]);

        return response()->json(['data' => $this->present($this->tasks->reassign($request->user(), $task, $data['assignee_id'] ?? null))]);
    }

    public function review(Request $request, Task $task)
    {
        $this->authorize('review', $task);
        $data = $request->validate(['approve' => ['required', 'boolean']]);

        return response()->json(['data' => $this->present($this->tasks->review($request->user(), $task, $data['approve']))]);
    }

    /** Explicit field list: never return raw models. */
    private function present(Task $t): array
    {
        return [
            'id' => $t->id, 'client_uuid' => $t->client_uuid, 'title' => $t->title, 'description' => $t->description,
            'priority' => $t->priority, 'status' => $t->status->value, 'review_status' => $t->review_status,
            'responsible_office_id' => $t->responsible_office_id, 'assignee_id' => $t->assignee_id,
            'due_date' => $t->due_date?->toDateString(), 'start_date' => $t->start_date?->toDateString(),
            'version' => $t->version, 'updated_at' => $t->updated_at?->toIso8601String(),
        ];
    }
}
