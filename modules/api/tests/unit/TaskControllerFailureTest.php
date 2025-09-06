<?php
declare(strict_types=1);

namespace app\modules\api\tests\unit;

use app\models\Task;
use app\modules\api\controllers\TaskController;
use app\modules\api\tests\support\AuthTestTrait;
use PHPUnit\Framework\TestCase;
use Yii;

final class TaskControllerFailureTest extends TestCase
{
    use AuthTestTrait;

    protected function setUp(): void
    {
        $this->ensureAdminAndTags();
    }

    public function testUnauthorizedCreate401(): void
    {
        $this->swapRequest(null, [], ['title' => 'No auth', 'due_date' => date('Y-m-d')]);
        $ctrl = new TaskController('task', Yii::$app);
        Yii::$app->controller = $ctrl;
        try {
            $ctrl->runAction('create');
        } catch (\yii\web\UnauthorizedHttpException $e) {
            $this->assertSame(401, $e->statusCode);
        }
    }

    public function testValidation422(): void
    {
        $payload = [
            'title' => 'Abc',
            'status' => Task::STATUS_PENDING,
            'due_date' => date('Y-m-d', strtotime('-1 day')),
            'assigned_to' => $this->adminId,
        ];
        $this->swapRequest($this->adminToken, [], $payload);
        $ctrl = new TaskController('task', Yii::$app);
        Yii::$app->controller = $ctrl;
        $res = $ctrl->runAction('create');
        $this->assertSame(422, Yii::$app->response->statusCode);
        $this->assertArrayHasKey('errors', $res);
        $this->assertArrayHasKey('title', $res['errors']);
        $this->assertArrayHasKey('due_date', $res['errors']);
    }

    public function testOptimisticLock409(): void
    {
        $this->swapRequest($this->adminToken, [], [
            'title' => 'Lock source', 'due_date' => date('Y-m-d'), 'assigned_to' => $this->adminId
        ]);
        $ctrl = new TaskController('task', Yii::$app);
        Yii::$app->controller = $ctrl;
        $task = $ctrl->runAction('create');
        $id = (int)$task['id'];

        $this->swapRequest($this->adminToken, [], ['title' => 'Conflict', 'version' => 100]);
        $res = $ctrl->runAction('update', ['id' => $id]);
        $this->assertSame(409, Yii::$app->response->statusCode);
        $this->assertArrayHasKey('error', $res);
    }
}