<?php
declare(strict_types=1);

namespace app\modules\api\tests\unit;

use app\modules\api\controllers\TaskController;
use app\modules\api\tests\support\AuthTestTrait;
use PHPUnit\Framework\TestCase;
use Yii;

final class TaskControllerEmptyTest extends TestCase
{
    use AuthTestTrait;

    protected function setUp(): void
    {
        $this->ensureAdminAndTags();
    }

    public function testEmptyByKeyword(): void
    {
        $this->swapRequest($this->adminToken, ['q' => '___no_such_phrase___']);
        $ctrl = new TaskController('task', Yii::$app);
        Yii::$app->controller = $ctrl;
        $res = $ctrl->runAction('index');
        $this->assertIsArray($res);
        $this->assertArrayHasKey('items', $res);
        $this->assertIsArray($res['items']);
        $this->assertCount(0, $res['items']);
    }
}