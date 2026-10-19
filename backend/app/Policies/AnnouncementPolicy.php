<?php

namespace App\Policies;

use App\Models\Announcement;
use App\Models\User;
use App\Services\VisibilityService;

class AnnouncementPolicy
{
    public function __construct(private VisibilityService $visibility) {}

    public function view(User $u, Announcement $a): bool
    {
        return $this->visibility->canViewAnnouncement($u, $a);
    }

    public function publish(User $u, int $officeId): bool
    {
        return $u->isActiveAccount() && $u->hasPermissionInOffice('announcement.publish', $officeId);
    }
}
