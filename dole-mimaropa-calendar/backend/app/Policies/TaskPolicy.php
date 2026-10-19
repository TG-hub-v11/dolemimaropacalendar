<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use App\Services\VisibilityService;

class TaskPolicy
{
    public function __construct(private VisibilityService $visibility) {}

    public function view(User $u, Task $t): bool
    {
        return $this->visibility->canViewTask($u, $t);
    }

    public function create(User $u, int $officeId): bool
    {
        return $u->isActiveAccount() && ($u->hasPermissionInOffice('task.manage', $officeId)
            || ($u->hasPermission('scope.regional') && $u->hasPermission('task.manage')));
    }

    /** Assignee may update progress/status on their own task; managers may update anything in scope. */
    public function updateStatus(User $u, Task $t): bool
    {
        return $this->visibility->canViewTask($u, $t) && ($u->id === $t->assignee_id || $this->manage($u, $t));
    }

    public function reassign(User $u, Task $t): bool
    {
        return $this->visibility->canViewTask($u, $t) && $this->manage($u, $t);
    }

    public function review(User $u, Task $t): bool
    {
        return $u->isActiveAccount() && $u->id !== $t->assignee_id
            && ($u->id === $t->reviewer_id || $this->manage($u, $t));
    }

    private function manage(User $u, Task $t): bool
    {
        return $u->hasPermissionInOffice('task.manage', $t->responsible_office_id)
            || ($u->hasPermission('scope.regional') && $u->hasPermission('task.manage'));
    }
}
