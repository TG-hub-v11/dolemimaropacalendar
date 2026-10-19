<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Blocked = 'blocked';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /** Explicit state machine: every allowed transition is listed here and enforced server-side. */
    public function allowedNext(): array
    {
        return match ($this) {
            self::Pending => [self::InProgress, self::Blocked, self::Cancelled],
            self::InProgress => [self::Blocked, self::Completed, self::Cancelled, self::Pending],
            self::Blocked => [self::InProgress, self::Cancelled],
            self::Completed => [self::InProgress], // reopen: administrators only (checked in policy)
            self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedNext(), true);
    }
}
