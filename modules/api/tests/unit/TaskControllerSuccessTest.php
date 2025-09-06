<?php
declare(strict_types=1);

namespace app\modules\api\tests\unit;

use app\models\Task;
use app\modules\api\controllers\TaskController;
use app\modules\api\tests\support\AuthTestTrait;
use PHPUnit\Framework\TestCase;
use Yii;

final class TaskControllerSuccessTest extends TestCase
{
    use AuthTestTrait;

    protected function setUp(): void
    {
        $this->ensureAdminAndTags();
    }

    public function testCreateViewUpdateToggleDeleteRestore(): void
    {
        // CREATE
        $payload = [
            'title' => 'Create via PHPUnit ' . time(),
            'status' => Task::STATUS_PENDING,
            'priority' => Task::PRIORITY_MEDIUM,
            'due_date' => date('Y-m-d', strtotime('+3 days')),
            'assigned_to' => $this->adminId,
            'tags' => array_slice($this->tagIds, 0, 2),
            'metadata' => ['from' => 'test'],
        ];
        $this->swapRequest($this->adminToken, [], $payload);
        $ctrl = new TaskController('task', Yii::$app);
        Yii::$app->controller = $ctrl;
        $created = $ctrl->runAction('create');
        $this->assertIsArray($created);
        $this->assertArrayHasKey('id', $created);
        $id = (int)$created['id'];

        // VIEW
        $this->swapRequest($this->adminToken);
        $view = $ctrl->runAction('view', ['id' => $id]);
        $this->assertSame($payload['title'], $view['title']);

        // UPDATE (optimistic lock)
        $this->swapRequest($this->adminToken, [], [
            'title' => $payload['title'] . ' (upd)',
            'version' => $view['version'],
        ]);
        $upd = $ctrl->runAction('update', ['id' => $id]);
        $this->assertStringEndsWith('(upd)', $upd['title']);

        // TOGGLE
        $this->swapRequest($this->adminToken);
        $toggled = $ctrl->runAction('toggle-status', ['id' => $id]);
        $this->assertArrayHasKey('status', $toggled);

        // DELETE (soft)
        $this->swapRequest($this->adminToken);
        $ctrl->runAction('delete', ['id' => $id]);
        $this->assertSame(204, Yii::$app->response->statusCode);

        // RESTORE
        $this->swapRequest($this->adminToken);
        $restored = $ctrl->runAction('restore', ['id' => $id]);
        $this->assertTrue((bool)($restored['restored'] ?? false));
    }
}