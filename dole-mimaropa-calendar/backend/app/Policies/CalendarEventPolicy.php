<?php

namespace App\Policies;

use App\Models\CalendarEvent;
use App\Models\User;
use App\Services\VisibilityService;

class CalendarEventPolicy
{
    public function __construct(private VisibilityService $visibility) {}

    public function view(User $u, CalendarEvent $e): bool
    {
        return $this->visibility->canViewEvent($u, $e);
    }

    public function create(User $u, int $officeId): bool
    {
        return $u->isActiveAccount() && $u->hasPermissionInOffice('event.manage', $officeId);
    }

    public function update(User $u, CalendarEvent $e): bool
    {
        return $u->isActiveAccount() && ($u->hasPermissionInOffice('event.manage', $e->owning_office_id)
            || ($u->hasPermission('scope.regional') && $u->hasPermission('event.manage')));
    }

    public function delete(User $u, CalendarEvent $e): bool
    {
        return $this->update($u, $e);
    }
}
