<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\CalendarEvent;
use App\Models\Office;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Single source of truth for "who may see what". Policies and list queries both use it,
 * so a record ID alone never grants access.
 *
 * Rules
 *  - Office-level users see records owned by, or shared with, offices where they hold a membership.
 *  - Regional visibility requires the `scope.regional` permission (Regional/System Administrator).
 *  - A direct relationship (organizer, participant, assignee, creator, reviewer) always grants view.
 *  - Inactive accounts see nothing.
 */
class VisibilityService
{
    public function officeIds(User $user): array
    {
        if (! $user->isActiveAccount()) {
            return [];
        }
        if ($user->hasPermission('scope.regional')) {
            return Office::where('is_active', true)->pluck('id')->all();
        }

        return $user->memberships()->pluck('office_id')->all();
    }

    public function canViewEvent(User $user, CalendarEvent $event): bool
    {
        if (! $user->isActiveAccount()) {
            return false;
        }
        if ($event->organizer_id === $user->id || $event->participants()->whereKey($user->id)->exists()) {
            return true;
        }
        $mine = $this->officeIds($user);
        if ($event->visibility === 'participants') {
            return false; // only organizer + invited participants, checked above
        }
        if (in_array($event->owning_office_id, $mine, true)) {
            return true;
        }

        return $event->visibility !== 'participants'
            && $event->offices()->whereIn('offices.id', $mine)->exists();
    }

    public function canViewTask(User $user, Task $task): bool
    {
        if (! $user->isActiveAccount()) {
            return false;
        }
        if (in_array($user->id, [$task->creator_id, $task->assignee_id, $task->reviewer_id], true)) {
            return true;
        }

        return in_array($task->responsible_office_id, $this->officeIds($user), true);
    }

    public function canViewAnnouncement(User $user, Announcement $a): bool
    {
        if (! $user->isActiveAccount()) {
            return false;
        }
        if ($a->author_id === $user->id) {
            return true;
        }
        if (! $a->published_at || $a->published_at->isFuture() || ($a->expires_at && $a->expires_at->isPast())) {
            return false;
        }

        return $a->audienceOffices()->whereIn('offices.id', $this->officeIds($user))->exists();
    }

    /** Query scopes mirror the per-record rules above. */
    public function scopeEvents(Builder $q, User $user): Builder
    {
        $mine = $this->officeIds($user);

        return $q->where(function (Builder $w) use ($user, $mine) {
            $w->where('organizer_id', $user->id)
              ->orWhereHas('participants', fn ($p) => $p->where('users.id', $user->id))
              ->orWhere(function (Builder $o) use ($mine) {
                  $o->where('visibility', '!=', 'participants')->where(function (Builder $x) use ($mine) {
                      $x->whereIn('owning_office_id', $mine)
                        ->orWhereHas('offices', fn ($of) => $of->whereIn('offices.id', $mine));
                  });
              });
        });
    }

    public function scopeTasks(Builder $q, User $user): Builder
    {
        $mine = $this->officeIds($user);

        return $q->where(function (Builder $w) use ($user, $mine) {
            $w->whereIn('responsible_office_id', $mine)
              ->orWhere('creator_id', $user->id)
              ->orWhere('assignee_id', $user->id)
              ->orWhere('reviewer_id', $user->id);
        });
    }
}
