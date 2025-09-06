<?php

namespace app\modules\api\controllers;

use app\models\Tag;
use app\models\Task;
use app\modules\api\Controller;
use Exception;
use Throwable;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\StaleObjectException;
use yii\rest\Serializer;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;

class TaskController extends Controller
{
    public $serializer = [
        'class' => Serializer::class,
        'collectionEnvelope' => 'items',
    ];

    /**
     * GET /tasks
     * filters: status, priority, assigned_to, due_date_from, due_date_to, due_date_range, tags, q, keyword
     * paginaation: page, per-page, limit, offset
     * sort: created_at, due_date, priority, title
     */
    public function actionIndex(): ActiveDataProvider
    {
        $req = Yii::$app->request;
        $q = Task::find()->alias('t')->with(['tags', 'assignee']);

        foreach (['status', 'priority'] as $enum) {
            $val = $req->get($enum);
            if ($val !== null && $val !== '') {
                $vals = is_array($val) ? $val : preg_split('/\s*,\s*/', (string)$val, -1, PREG_SPLIT_NO_EMPTY);
                if ($enum === 'status') {
                    $allowed = [Task::STATUS_PENDING, Task::STATUS_IN_PROGRESS, Task::STATUS_COMPLETED];
                } else {
                    $allowed = [Task::PRIORITY_LOW, Task::PRIORITY_MEDIUM, Task::PRIORITY_HIGH];
                }
                $vals = array_values(array_intersect($vals, $allowed));
                if ($vals) {
                    $q->andWhere(['t.' . $enum => $vals]);
                }
            }
        }

        $assigned = $req->get('assigned_to');
        if ($assigned !== null && $assigned !== '') {
            $ids = is_array($assigned) ? $assigned : preg_split('/\s*,\s*/', (string)$assigned, -1, PREG_SPLIT_NO_EMPTY);
            $ids = array_filter(array_map('intval', $ids));
            if ($ids) {
                $q->andWhere(['t.assigned_to' => $ids]);
            }
        }

        $from = $req->get('due_date_from');
        $to = $req->get('due_date_to');
        $range = $req->get('due_date_range');
        if ($range && !$from && !$to) {
            [$from, $to] = array_pad(preg_split('/\s*,\s*/', (string)$range), 2, null);
        }
        if ($from) $q->andWhere(['>=', 't.due_date', $from]);
        if ($to) $q->andWhere(['<=', 't.due_date', $to]);

        $tags = $req->get('tags');
        if ($tags !== null && $tags !== '') {
            $tagIds = is_array($tags) ? $tags : preg_split('/\s*,\s*/', (string)$tags, -1, PREG_SPLIT_NO_EMPTY);
            $tagIds = array_filter(array_map('intval', $tagIds));
            if ($tagIds) {
                $q->join('INNER JOIN', 'tasks_tags tt', 'tt.task_id = t.id')
                    ->andWhere(['tt.tag_id' => $tagIds])
                    ->groupBy('t.id');
            }
        }

        $kw = $req->get('q') ?? $req->get('keyword');
        if ($kw !== null && $kw !== '') {
            $kw = trim((string)$kw);
            $q->andWhere(['or',
                ['like', 't.title', $kw],
                ['like', 't.description', $kw],
            ]);
        }

        $allowedSort = ['created_at', 'due_date', 'priority', 'title'];
        $sortParam = $req->get('sort');
        $sort = [];
        if ($sortParam) {
            foreach (preg_split('/\s*,\s*/', (string)$sortParam, -1, PREG_SPLIT_NO_EMPTY) as $field) {
                $dir = SORT_ASC;
                if (strncmp($field, '-', 1) === 0) {
                    $dir = SORT_DESC;
                    $field = substr($field, 1);
                }
                if (in_array($field, $allowedSort, true)) {
                    $sort[$field] = $dir;
                }
            }
        }
        if (!$sort) $sort = ['created_at' => SORT_DESC];

        $limit = $req->get('limit');
        $offset = $req->get('offset');
        $per = $req->get('per-page');
        $page = $req->get('page');

        if ($limit !== null || $offset !== null) {
            $limit = (int)($limit ?? 20);
            $offset = (int)($offset ?? 0);
            $pagination = [
                'pageSize' => max(1, $limit),
                'page' => (int)floor($offset / max(1, $limit)),
            ];
        } elseif ($per !== null || $page !== null) {
            $pagination = [
                'pageSize' => max(1, (int)($per ?? 20)),
                'page' => max(0, (int)($page ?? 1) - 1),
            ];
        } else {
            $pagination = [
                'pageSize' => 20,
            ];
        }

        return new ActiveDataProvider([
            'query' => $q,
            'pagination' => array_merge($pagination, [
                'params' => Yii::$app->request->getQueryParams(),
                'route' => 'api/tasks',
            ]),
            'sort' => [
                'attributes' => $allowedSort,
                'defaultOrder' => $sort,
            ],
        ]);
    }

    /**
     * GET /tasks/{id}
     * included tags and assigned user
     * @throws NotFoundHttpException
     */
    public function actionView(int $id): array
    {
        $model = Task::find()->alias('t')->with(['tags', 'assignee'])->where(['t.id' => $id])->one();
        if (!$model) throw new NotFoundHttpException('Task not found');

        return $model->toArray([], ['tags', 'assignee']);
    }

    /**
     * POST /tasks
     * included tags
     * @throws Throwable
     */
    public function actionCreate(): array
    {
        $body = Yii::$app->request->getBodyParams();
        $tags = $body['tags'] ?? null;
        unset($body['tags']);

        $task = new Task();
        $task->load($body, '');

        $tx = Yii::$app->db->beginTransaction();
        try {
            if (!$task->save()) {
                $tx->rollBack();
                Yii::$app->response->statusCode = 422;
                return ['errors' => $task->getErrors()];
            }

            if (is_array($tags) && $tags) {
                $this->syncTags($task, $tags);
            }

            $task->log('create');
            $tx->commit();
            Yii::$app->response->statusCode = 201;
            return $task->toArray([], ['tags', 'assignee']);
        } catch (Throwable $e) {
            $tx->rollBack();
            throw $e;
        }
    }

    /**
     * PUT /tasks/{id}
     * version should be correct.
     * included tags
     * @throws Exception|Throwable
     */
    public function actionUpdate(int $id)
    {
        $task = $this->findModel($id);
        if (!$this->canManage($task)) {
            Yii::$app->response->statusCode = 403;
            return ['error' => 'Forbidden'];
        }

        $body = Yii::$app->request->getBodyParams();
        if (!array_key_exists('version', $body)) {
            throw new BadRequestHttpException('Field "version" is required for optimistic locking.');
        }
        $tags = $body['tags'] ?? null;
        unset($body['tags']);

        $task->load($body, '');

        $tx = Yii::$app->db->beginTransaction();
        try {
            try {
                if (!$task->save()) {
                    $tx->rollBack();
                    Yii::$app->response->statusCode = 422;
                    return ['errors' => $task->getErrors()];
                }
            } catch (StaleObjectException) {
                $tx->rollBack();
                Yii::$app->response->statusCode = 409;
                return ['error' => 'Version conflict. Reload the task and retry.'];
            }

            if (is_array($tags)) {
                $this->syncTags($task, $tags);
            }

            $tx->commit();
            return $task->toArray([], ['tags', 'assignee']);
        } catch (Throwable $e) {
            $tx->rollBack();
            throw $e;
        }
    }

    /**
     * PATCH /tasks/{id}/toggle-status
     * @throws Throwable
     */
    public function actionToggleStatus(int $id): array
    {
        $task = $this->findModel($id);

        if (!$this->canManage($task)) {
            Yii::$app->response->statusCode = 403;
            return ['error' => 'Forbidden'];
        }

        $map = [
            Task::STATUS_PENDING => Task::STATUS_IN_PROGRESS,
            Task::STATUS_IN_PROGRESS => Task::STATUS_COMPLETED,
            Task::STATUS_COMPLETED => Task::STATUS_PENDING,
        ];

        $task->status = $map[$task->status] ?? Task::STATUS_PENDING;
        if ($task->save(false, ['status', 'updated_at', 'version'])) {
            return ['id' => $task->id, 'status' => $task->status, 'version' => $task->version];
        } else {
            Yii::$app->response->statusCode = 422;
            return ['errors' => $task->getErrors()];
        }
    }

    /**
     * DELETE /tasks/{id}  — soft delete
     * @throws Throwable
     */
    public function actionDelete(int $id): ?array
    {
        $task = $this->findModel($id);

        if (!$this->canManage($task)) {
            Yii::$app->response->statusCode = 403;
            return ['error' => 'Forbidden'];
        }

        if ($task->softDelete()) {
            Yii::$app->response->statusCode = 204;
            return null;
        } else {
            Yii::$app->response->statusCode = 422;
            return ['errors' => $task->getErrors()];

        }
    }

    /**
     * PATCH /tasks/{id}/restore
     * @throws Throwable
     */
    public function actionRestore(int $id): array
    {

        $task = Task::find()->withTrashed()->where(['t.id' => $id])->one();

        if (!$this->canManage($task)) {
            Yii::$app->response->statusCode = 403;
            return ['error' => 'Forbidden'];
        }

        if (!$task) throw new NotFoundHttpException('Task not found (trashed or not).');

        if ($task->deleted_at === null) {
            return ['id' => $task->id, 'restored' => false];
        }

        $task->deleted_at = null;
        if ($task->save(false, ['deleted_at', 'updated_at'])) {
            $task->log('restore');
            return ['id' => $task->id, 'restored' => true];
        } else {
            Yii::$app->response->statusCode = 422;
            return ['errors' => $task->getErrors()];
        }
    }

    /**
     * @throws NotFoundHttpException
     */
    protected function findModel(int $id): array|Task
    {
        $m = Task::find()->where(['t.id' => $id])->one();
        if (!$m) throw new NotFoundHttpException('Task not found');

        return $m;
    }

    /**
     * sync by id
     */
    protected function syncTags(Task $task, array $tagIds): void
    {
        $tagIds = array_values(array_unique(array_filter(array_map('intval', $tagIds))));
        $task->unlinkAll('tags', true);
        if (!$tagIds) return;

        $tags = Tag::find()->where(['id' => $tagIds])->all();
        foreach ($tags as $tag) {
            $task->link('tags', $tag);
        }
    }

    /**
     * role checker
     */
    protected function canManage(Task $task): bool
    {
        $payload = Yii::$app->params['jwt_payload'] ?? [];
        $role = $payload['role'] ?? ($task->assignee->role ?? null);
        if ($role === 'admin') return true;
        return (Yii::$app->user->id && (int)$task->assigned_to === (int)Yii::$app->user->id);
    }
}
