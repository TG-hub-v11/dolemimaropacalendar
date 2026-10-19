<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use PHPUnit\Framework\TestCase;

class TaskStateTransitionTest extends TestCase
{
    public function test_allowed_and_forbidden_transitions(): void
    {
        $this->assertTrue(TaskStatus::Pending->canTransitionTo(TaskStatus::InProgress));
        $this->assertTrue(TaskStatus::InProgress->canTransitionTo(TaskStatus::Completed));
        $this->assertFalse(TaskStatus::Pending->canTransitionTo(TaskStatus::Completed));
        $this->assertFalse(TaskStatus::Cancelled->canTransitionTo(TaskStatus::InProgress));
        $this->assertFalse(TaskStatus::Blocked->canTransitionTo(TaskStatus::Completed));
    }
}
