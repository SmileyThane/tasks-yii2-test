<?php
declare(strict_types=1);

namespace app\modules\api\tests\unit;

use app\models\Task;
use PHPUnit\Framework\TestCase;
use yii\db\StaleObjectException;

final class TaskOptimisticLockTest extends TestCase
{
    public function testVersionIncrementsOnSave(): void
    {
        $t = new Task(['title' => 'OL', 'due_date' => date('Y-m-d'), 'version' => 1]);
        $t->save(false);
        $v = $t->version;
        $t->title = 'OL2';
        $t->version + 1;
        $t->save(false);
        $this->assertSame($v + 1, $t->version);
    }

    public function testStaleObjectExceptionOnConflict(): void
    {
        $t = new Task(['title' => 'Race', 'due_date' => date('Y-m-d')]);
        $t->save(false);

        $a = Task::findOne($t->id);
        $b = Task::findOne($t->id);

        $a->title = 'A';
        $a->save(false);

        $this->expectException(StaleObjectException::class);
        $b->title = 'B';
        $b->update(false);
    }
}