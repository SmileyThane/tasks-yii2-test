<?php
declare(strict_types=1);

namespace app\modules\api\tests\unit;

use app\models\Task;
use PHPUnit\Framework\TestCase;

final class TaskValidationTest extends TestCase
{
    public function testDueDateCannotBePastForPending(): void
    {
        $t = new Task([
            'title' => 'Bad due',
            'status' => Task::STATUS_PENDING,
            'due_date' => date('Y-m-d', strtotime('-2 days')),
        ]);
        $this->assertFalse($t->validate());
        $this->assertArrayHasKey('due_date', $t->getErrors());
    }

    public function testAssignedUserMustExist(): void
    {
        $t = new Task([
            'title' => 'No such user',
            'assigned_to' => 999999,
            'due_date' => date('Y-m-d'),
        ]);
        $t->validate();
        $this->assertArrayHasKey('assigned_to', $t->getErrors());
    }
}