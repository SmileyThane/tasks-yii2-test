<?php
declare(strict_types=1);

namespace app\modules\api\tests\unit;

use app\models\Tag;
use app\models\Task;
use app\modules\api\controllers\TaskController;
use app\modules\api\tests\support\AuthTestTrait;
use PHPUnit\Framework\TestCase;
use Yii;

final class TaskControllerFiltersPaginationTest extends TestCase
{
    use AuthTestTrait;

    protected function setUp(): void
    {
        $this->ensureAdminAndTags();

        if (!Task::find()->exists()) {
            $mk = function ($title, $status, $prio, $due, $tags = []) {
                $t = new Task([
                    'title' => $title, 'status' => $status, 'priority' => $prio,
                    'due_date' => $due, 'assigned_to' => $this->adminId,
                    'created_at' => time(), 'updated_at' => time(),
                ]);
                $t->save(false);
                foreach ($tags as $tid) {
                    if ($g = Tag::findOne($tid)) $t->link('tags', $g);
                }
                return $t;
            };
            $ids = $this->tagIds;
            $mk('Finish docs', Task::STATUS_COMPLETED, Task::PRIORITY_HIGH, date('Y-m-d', strtotime('-1 day')), array_slice($ids, 0, 1));
            $mk('Fix auth bug', Task::STATUS_IN_PROGRESS, Task::PRIORITY_HIGH, date('Y-m-d', strtotime('+2 days')), array_slice($ids, 1, 1));
            $mk('Write tests', Task::STATUS_PENDING, Task::PRIORITY_MEDIUM, date('Y-m-d', strtotime('+5 days')), array_slice($ids, 0, 2));
            $mk('Refactor service', Task::STATUS_PENDING, Task::PRIORITY_LOW, date('Y-m-d', strtotime('+10 days')));
        }
    }

    public function testCombineFiltersAndSort(): void
    {
        $tagIds = array_slice($this->tagIds, 0, 2);
        $this->swapRequest($this->adminToken, [
            'status' => 'completed,in_progress',
            'priority' => 'high',
            'assigned_to' => (string)$this->adminId,
            'tags' => implode(',', $tagIds),
            'sort' => '-due_date,title',
            'page' => 1,
            'per-page' => 10,
        ]);
        $ctrl = new TaskController('task', Yii::$app);
        Yii::$app->controller = $ctrl;
        $res = $ctrl->runAction('index');

        $items = $res['items'] ?? [];
        foreach ($items as $it) {
            $this->assertContains($it['status'], [Task::STATUS_COMPLETED, Task::STATUS_IN_PROGRESS]);
            $this->assertSame(Task::PRIORITY_HIGH, $it['priority']);
            $this->assertEquals($this->adminId, $it['assigned_to']);
        }
        if (count($items) >= 2 && $items[0]['due_date'] && $items[1]['due_date']) {
            $this->assertTrue(strtotime($items[0]['due_date']) >= strtotime($items[1]['due_date']));
        }
    }

    public function testOffsetPagination(): void
    {
        $ctrl = new TaskController('task', Yii::$app);
        Yii::$app->controller = $ctrl;

        $this->swapRequest($this->adminToken, ['limit' => 2, 'offset' => 0, 'sort' => '-id']);
        $first = $ctrl->runAction('index')['items'];

        $this->swapRequest($this->adminToken, ['limit' => 2, 'offset' => 2, 'sort' => '-id']);
        $second = $ctrl->runAction('index')['items'];

        $ids1 = array_map(fn($x) => $x['id'], $first);
        $ids2 = array_map(fn($x) => $x['id'], $second);
        $this->assertEmpty(array_intersect($ids1, $ids2));
    }
}