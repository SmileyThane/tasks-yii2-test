<?php

namespace app\commands;

use JetBrains\PhpStorm\NoReturn;
use yii\base\Exception;
use yii\console\Controller;
use yii\console\ExitCode;
use Faker\Factory as Faker;
use app\models\User;
use app\models\Tag;
use app\models\Task;

class SeedController extends Controller
{
    public int $users = 5;
    public int $tags = 15;
    public int $tasks = 50;
    public int $soft = 0;

    public int $count = 5;
    public int $admin = 1;

    public int $minTags = 0;
    public int $maxTags = 3;

    public function options($actionID): array
    {
        return match ($actionID) {
            'all' => ['users', 'tags', 'tasks', 'soft'],
            'users' => ['count', 'admin'],
            'tags' => ['count'],
            'tasks' => ['count', 'minTags', 'maxTags', 'soft'],
            default => [],
        };
    }

    public function optionAliases(): array
    {
        return [
            'u' => 'users',
            't' => 'tags',
            'min-tags' => 'minTags',
            'max-tags' => 'maxTags',
        ];
    }


    /**
     * yii seed/all --users=5 --tags=15 --tasks=50 --soft=5
     * @throws Exception
     */
    #[NoReturn]
    public function actionAll(int $users = 5, int $tags = 15, int $tasks = 50, int $soft = 0): int
    {
        $users = $this->users ?? $users;
        $tags = $this->tags ?? $tags;
        $tasks = $this->tasks ?? $tasks;
        $soft = $this->soft ?? $soft;

        $this->actionUsers($this->count = $users, $this->admin);
        $this->actionTags($this->count = $tags);
        $this->actionTasks($this->count = $tasks, $this->minTags, $this->maxTags, $soft);
        $this->stdout("Seed complete\n");
        return ExitCode::OK;
    }

    /**
     * yii seed/users --count=5 --admin=1
     * Auth Data: admin@example.com / admin123, userX@example.com / user123
     * @throws Exception
     */
    public function actionUsers(int $count = 5, int $admin = 1): int
    {
        $count = $count ?? $this->count;
        $admin = $admin ?? $this->admin;
        $faker = Faker::create();
        $now = time();

        if ($admin) {
            $adminUser = User::find()->where(['email' => 'admin@example.com'])->one() ?? new User();
            $adminUser->setAttributes([
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'role' => 'admin',
                'created_at' => $now,
                'updated_at' => $now,
            ], false);
            if (!$adminUser->password_hash) {
                $adminUser->setPassword('admin123');
            }
            if (!$adminUser->save()) {
                $this->stderr("Admin user error: " . json_encode($adminUser->getErrors()) . "\n");
            }
        }

        for ($i = 1; $i <= $count; $i++) {
            $email = "user$i@example.com";
            $user = User::find()->where(['email' => $email])->one() ?? new User();
            $user->setAttributes([
                'name' => $faker->name(),
                'email' => $email,
                'role' => 'user',
                'created_at' => $now,
                'updated_at' => $now,
            ], false);
            if (!$user->password_hash) {
                $user->setPassword('user123');
            }
            if (!$user->save()) {
                $this->stderr("User$i error: " . json_encode($user->getErrors()) . "\n");
            }
        }

        $this->stdout("Users seeded\n");
        return ExitCode::OK;
    }

    /**
     * yii seed/tags --count=15
     * @throws \yii\db\Exception
     */
    public function actionTags(int $count = 15): int
    {
        $count = $count ?? $this->count;
        $faker = Faker::create();
        $now = time();

        $used = Tag::find()->select('name')->column();
        $used = array_fill_keys($used, true);

        for ($i = 0; $i < $count; $i++) {
            for ($tries = 0; $tries < 10; $tries++) {
                $name = ucfirst($faker->unique()->word());
                if (!isset($used[$name])) {
                    $used[$name] = true;
                    break;
                }
            }

            $tag = new Tag();
            $tag->setAttributes([
                'name' => $name,
                'color' => sprintf('#%06X', mt_rand(0, 0xFFFFFF)),
                'created_at' => $now,
                'updated_at' => $now,
            ], false);

            if (!$tag->save()) {
                $this->stderr("Tag error: " . json_encode($tag->getErrors()) . "\n");
            }
        }

        $this->stdout("Tags seeded\n");
        return ExitCode::OK;
    }

    /**
     * yii seed/tasks --count=50 --min-tags=0 --max-tags=3 --soft=5
     * @throws \yii\db\Exception
     */
    public function actionTasks(int $count = 50, int $minTags = 0, int $maxTags = 3, int $soft = 0): int
    {
        $count = $count ?? $this->count;
        $minTags = $minTags ?? $this->minTags;
        $maxTags = $maxTags ?? $this->maxTags;
        $soft = $soft ?? $this->soft;
        $faker = Faker::create();
        $now = time();

        $userIds = User::find()->select('id')->column();
        $tagIds = Tag::find()->select('id')->column();

        if (empty($userIds)) {
            $this->stderr("No users to assign\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }
        if (empty($tagIds)) {
            $this->stderr("No tags to link\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $statuses = [Task::STATUS_PENDING, Task::STATUS_IN_PROGRESS, Task::STATUS_COMPLETED];
        $priorities = [Task::PRIORITY_LOW, Task::PRIORITY_MEDIUM, Task::PRIORITY_HIGH];

        $created = [];
        for ($i = 0; $i < $count; $i++) {
            $status = $faker->randomElement($statuses);
            $priority = $faker->randomElement($priorities);
            $assignee = $faker->randomElement($userIds);

            if (in_array($status, [Task::STATUS_PENDING, Task::STATUS_IN_PROGRESS], true)) {
                $dueDate = date('Y-m-d', strtotime('+' . mt_rand(0, 30) . ' days'));
            } else {
                $dueDate = mt_rand(0, 1)
                    ? date('Y-m-d', strtotime('-' . mt_rand(0, 30) . ' days'))
                    : date('Y-m-d', strtotime('+' . mt_rand(0, 30) . ' days'));
            }

            $task = new Task();
            $task->setAttributes([
                'title' => $faker->sentence(mt_rand(3, 6)),
                'description' => $faker->optional(0.7)->paragraph(),
                'status' => $status,
                'priority' => $priority,
                'due_date' => $dueDate,
                'assigned_to' => $assignee,
                'metadata' => [
                    'source' => $faker->randomElement(['OKR', 'Backlog', 'Ops', 'Support']),
                    'estimate_h' => mt_rand(1, 16),
                ],
                'created_at' => $now,
                'updated_at' => $now,
            ], false);

            if (!$task->save()) {
                if ($task->getErrors('due_date')) {
                    $task->due_date = date('Y-m-d');
                    if (!$task->save()) {
                        $this->stderr("Task error: " . json_encode($task->getErrors()) . "\n");
                        continue;
                    }
                } else {
                    $this->stderr("Task error: " . json_encode($task->getErrors()) . "\n");
                    continue;
                }
            }

            $n = mt_rand($minTags, max($minTags, $maxTags));
            if ($n > 0) {
                $picked = array_slice($faker->randomElements($tagIds, min($n, count($tagIds))), 0, $n);
                foreach ($picked as $tid) {
                    $tag = Tag::findOne($tid);
                    if ($tag) $task->link('tags', $tag);
                }
            }

            $created[] = $task->id;
        }

        $soft = min($soft, count($created));
        if ($soft > 0) {
            $ids = array_slice($faker->randomElements($created, $soft), 0, $soft);
            foreach ($ids as $id) {
                $t = Task::find()->where(['t.id' => $id])->one();
                $t?->softDelete();
            }
        }

        $this->stdout("Tasks seeded ($count, soft=$soft)\n");
        return ExitCode::OK;
    }
}
