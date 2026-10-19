<?php

namespace Tests\Feature;

use App\Models\CalendarEvent;
use App\Models\Office;
use App\Models\OfficeMembership;
use App\Models\Organization;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\AccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Uses a disposable test database (phpunit.xml: sqlite :memory:) and synthetic data only. */
class CrossOfficeIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Office $a;
    private Office $b;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AccessSeeder::class);
        $org = Organization::create(['name' => 'Test Org']);
        $this->a = Office::create(['organization_id' => $org->id, 'code' => 'A', 'name' => 'A']);
        $this->b = Office::create(['organization_id' => $org->id, 'code' => 'B', 'name' => 'B']);
    }

    private function user(Office $office, string $role): User
    {
        $u = User::factory()->create(['primary_office_id' => $office->id, 'is_active' => true]);
        OfficeMembership::create(['user_id' => $u->id, 'office_id' => $office->id,
            'role_id' => Role::where('slug', $role)->value('id'), 'is_primary' => true]);

        return $u;
    }

    private function taskIn(Office $o, User $creator, ?User $assignee = null): Task
    {
        $t = new Task(['client_uuid' => (string) Str::uuid(), 'responsible_office_id' => $o->id,
            'assignee_id' => $assignee?->id, 'title' => 'Synthetic task']);
        $t->creator_id = $creator->id;
        $t->save();

        return $t;
    }

    public function test_office_user_cannot_view_another_offices_task_by_id(): void
    {
        $adminB = $this->user($this->b, 'office-admin');
        $staffA = $this->user($this->a, 'personnel');
        $task = $this->taskIn($this->b, $adminB);

        Sanctum::actingAs($staffA);
        $this->getJson("/api/v1/tasks/{$task->id}")->assertForbidden();
    }

    public function test_task_list_only_contains_visible_tasks(): void
    {
        $adminA = $this->user($this->a, 'office-admin');
        $adminB = $this->user($this->b, 'office-admin');
        $mine = $this->taskIn($this->a, $adminA);
        $this->taskIn($this->b, $adminB);

        Sanctum::actingAs($adminA);
        $ids = collect($this->getJson('/api/v1/tasks')->assertOk()->json('data'))->pluck('id');
        $this->assertEquals([$mine->id], $ids->all());
    }

    public function test_regional_admin_sees_all_offices_but_office_admin_does_not(): void
    {
        $regional = $this->user($this->a, 'regional-admin');
        $adminA = $this->user($this->a, 'office-admin');
        $taskB = $this->taskIn($this->b, $this->user($this->b, 'office-admin'));

        Sanctum::actingAs($regional);
        $this->getJson("/api/v1/tasks/{$taskB->id}")->assertOk();

        Sanctum::actingAs($adminA);
        $this->getJson("/api/v1/tasks/{$taskB->id}")->assertForbidden();
    }

    public function test_cannot_create_task_in_office_without_manage_permission(): void
    {
        Sanctum::actingAs($this->user($this->a, 'personnel'));
        $this->postJson('/api/v1/tasks', [
            'client_uuid' => (string) Str::uuid(), 'responsible_office_id' => $this->a->id, 'title' => 'x',
        ])->assertForbidden();

        Sanctum::actingAs($this->user($this->a, 'office-admin'));
        $this->postJson('/api/v1/tasks', [
            'client_uuid' => (string) Str::uuid(), 'responsible_office_id' => $this->b->id, 'title' => 'x',
        ])->assertForbidden();
    }

    public function test_inactive_user_is_rejected(): void
    {
        $u = $this->user($this->a, 'personnel');
        $u->update(['is_active' => false]);
        Sanctum::actingAs($u);
        $this->getJson('/api/v1/tasks')->assertForbidden();
    }

    public function test_event_hidden_from_other_office_unless_shared(): void
    {
        $adminA = $this->user($this->a, 'office-admin');
        $staffB = $this->user($this->b, 'personnel');
        $event = CalendarEvent::create([
            'client_uuid' => (string) Str::uuid(), 'owning_office_id' => $this->a->id, 'organizer_id' => $adminA->id,
            'title' => 'Synthetic', 'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour(),
        ]);
        Sanctum::actingAs($staffB);
        $this->getJson("/api/v1/events/{$event->id}")->assertForbidden();

        $event->offices()->attach($this->b->id);
        $this->getJson("/api/v1/events/{$event->id}")->assertOk();
    }

    public function test_event_participants_only_visibility_blocks_office_members(): void
    {
        $adminA = $this->user($this->a, 'office-admin');
        $staffA = $this->user($this->a, 'personnel');
        $event = CalendarEvent::create([
            'client_uuid' => (string) Str::uuid(), 'owning_office_id' => $this->a->id, 'organizer_id' => $adminA->id,
            'title' => 'Closed', 'visibility' => 'participants', 'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour(),
        ]);
        Sanctum::actingAs($staffA);
        $this->getJson("/api/v1/events/{$event->id}")->assertForbidden();
    }
}
