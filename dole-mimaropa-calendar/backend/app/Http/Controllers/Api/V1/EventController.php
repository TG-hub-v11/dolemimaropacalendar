<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use App\Services\VisibilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EventController extends Controller
{
    public function __construct(private VisibilityService $visibility) {}

    public function index(Request $request)
    {
        $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date']]);
        $q = $this->visibility->scopeEvents(CalendarEvent::query(), $request->user())
            ->when($request->query('from'), fn ($q, $d) => $q->where(fn ($w) => $w->where('ends_at', '>=', $d)->orWhere('end_date', '>=', $d)->orWhereNotNull('rrule')))
            ->when($request->query('to'), fn ($q, $d) => $q->where(fn ($w) => $w->where('starts_at', '<=', $d)->orWhere('start_date', '<=', $d)->orWhereNotNull('rrule')))
            ->when($request->query('category'), fn ($q, $c) => $q->where('category', $c))
            ->when($request->query('q'), fn ($q, $s) => $q->where('title', 'like', '%'.addcslashes($s, '%_\\').'%'))
            ->orderBy('starts_at');

        return response()->json($q->paginate(min((int) $request->query('per_page', 50), 200))
            ->through(fn ($e) => $this->present($e)));
    }

    public function show(CalendarEvent $event)
    {
        $this->authorize('view', $event);

        return response()->json(['data' => $this->present($event)]);
    }

    public function store(Request $request)
    {
        $data = $this->rules($request);
        $this->authorize('create', [CalendarEvent::class, (int) $data['owning_office_id']]);

        $event = DB::transaction(function () use ($request, $data) {
            if ($dup = CalendarEvent::where('client_uuid', $data['client_uuid'])->first()) {
                return $dup;
            }
            $offices = $data['office_ids'] ?? [];
            unset($data['office_ids']);
            $event = CalendarEvent::create($data + ['organizer_id' => $request->user()->id]);
            $event->offices()->sync($offices);

            return $event;
        });

        return response()->json(['data' => $this->present($event)], 201);
    }

    public function update(Request $request, CalendarEvent $event)
    {
        $this->authorize('update', $event);
        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'venue' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['sometimes', 'date'],
            'ends_at' => ['sometimes', 'date', 'after_or_equal:starts_at'],
            'version' => ['required', 'integer'],
        ]);
        $expected = $data['version'];
        unset($data['version']);

        // Optimistic concurrency: never overwrite a newer server version with an older edit.
        $affected = CalendarEvent::whereKey($event->id)->where('version', $expected)
            ->update($data + ['status' => 'updated', 'version' => $expected + 1, 'updated_at' => now()]);
        if (! $affected) {
            return response()->json(['error' => ['code' => 'version_conflict', 'message' => 'Event changed on the server.',
                'server' => $this->present($event->fresh())]], 409);
        }

        return response()->json(['data' => $this->present($event->fresh())]);
    }

    public function cancel(CalendarEvent $event)
    {
        $this->authorize('update', $event);
        $event->update(['status' => 'cancelled']);

        return response()->json(['data' => $this->present($event->fresh())]);
    }

    private function rules(Request $request): array
    {
        return $request->validate([
            'client_uuid' => ['required', 'uuid'],
            'owning_office_id' => ['required', 'exists:offices,id'],
            'office_ids' => ['sometimes', 'array'],
            'office_ids.*' => ['exists:offices,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'category' => ['sometimes', 'string', 'max:48'],
            'is_all_day' => ['sometimes', 'boolean'],
            'starts_at' => ['required_if:is_all_day,false', 'nullable', 'date'],
            'ends_at' => ['required_if:is_all_day,false', 'nullable', 'date', 'after_or_equal:starts_at'],
            'start_date' => ['required_if:is_all_day,true', 'nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'venue' => ['nullable', 'string', 'max:255'],
            'online_link' => ['nullable', 'url:https', 'max:2048'],
            'visibility' => ['sometimes', Rule::in(['office', 'participants', 'regional'])],
        ]);
    }

    private function present(CalendarEvent $e): array
    {
        return [
            'id' => $e->id, 'client_uuid' => $e->client_uuid, 'title' => $e->title, 'description' => $e->description,
            'category' => $e->category, 'is_all_day' => $e->is_all_day,
            'starts_at' => $e->starts_at?->toIso8601String(), 'ends_at' => $e->ends_at?->toIso8601String(),
            'start_date' => $e->start_date?->toDateString(), 'end_date' => $e->end_date?->toDateString(),
            'venue' => $e->venue, 'online_link' => $e->online_link, 'status' => $e->status,
            'owning_office_id' => $e->owning_office_id, 'organizer_id' => $e->organizer_id,
            'version' => $e->version,
        ];
    }
}
