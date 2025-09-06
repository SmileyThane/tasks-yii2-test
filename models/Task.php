<?php

namespace app\models;

use DateTime;
use Yii;
use yii\base\InvalidConfigException;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Exception;

/**
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property string $status      pending|in_progress|completed
 * @property string $priority    low|medium|high
 * @property string|null $due_date    Y-m-d
 * @property int|null $assigned_to
 * @property string|null $metadata    JSON text
 * @property int $created_at
 * @property int $updated_at
 * @property int|null $deleted_at
 * @property int $version
 *
 * @property User|null $assignee
 * @property Tag[] $tags
 */
class Task extends ActiveRecord
{
    public const string STATUS_PENDING = 'pending';
    public const string STATUS_IN_PROGRESS = 'in_progress';
    public const string STATUS_COMPLETED = 'completed';

    public const string PRIORITY_LOW = 'low';
    public const string PRIORITY_MEDIUM = 'medium';
    public const string PRIORITY_HIGH = 'high';

    public static function tableName(): string
    {
        return 'tasks';
    }

    public static function find(): TaskQuery
    {
        return (new TaskQuery(static::class))->alias('t');
    }

    public function behaviors(): array
    {
        return [
            TimestampBehavior::class,
        ];
    }

    public function optimisticLock(): string
    {
        return 'version';
    }

    public function rules(): array
    {
        return [
            [['title'], 'required'],
            [['title'], 'string', 'min' => 5, 'max' => 255],
            [['description'], 'string'],

            [['status'], 'in', 'range' => [
                self::STATUS_PENDING, self::STATUS_IN_PROGRESS, self::STATUS_COMPLETED
            ]],
            [['priority'], 'in', 'range' => [
                self::PRIORITY_LOW, self::PRIORITY_MEDIUM, self::PRIORITY_HIGH
            ]],
            [['status'], 'default', 'value' => self::STATUS_PENDING],
            [['priority'], 'default', 'value' => self::PRIORITY_MEDIUM],

            [['due_date'], 'date', 'format' => 'php:Y-m-d'],
            ['due_date', 'validateDueDateForStatus'],

            [['assigned_to', 'created_at', 'updated_at', 'deleted_at', 'version'], 'integer'],
            ['assigned_to', 'exist', 'targetClass' => User::class, 'targetAttribute' => ['assigned_to' => 'id'], 'skipOnEmpty' => true, 'skipOnError' => true],

            [['metadata'], 'safe'],

        ];
    }

    public function fields(): array
    {
        $fields = parent::fields();
        $fields['metadata'] = function () {
            if ($this->metadata === null || $this->metadata === '') return null;
            $decoded = json_decode($this->metadata, true);
            return json_last_error() === JSON_ERROR_NONE ? $decoded : $this->metadata;
        };
        return $fields;
    }

    public function beforeSave($insert): bool
    {
        if (is_array($this->metadata) || is_object($this->metadata)) {
            $this->metadata = json_encode($this->metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        return parent::beforeSave($insert);
    }

    /**
     * @throws Exception
     */
    public function softDelete(): bool
    {
        $this->deleted_at = time();
        $ok = $this->save(false, ['deleted_at','updated_at']);
        if ($ok) $this->log('delete');
        return $ok;
    }

    public function getAssignee(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'assigned_to']);
    }

    /**
     * @throws InvalidConfigException
     */
    public function getTags(): ActiveQuery
    {
        return $this->hasMany(Tag::class, ['id' => 'tag_id'])
            ->viaTable('tasks_tags', ['task_id' => 'id']);
    }

    public function validateDueDateForStatus($attribute): void
    {
        if (!$this->$attribute) return;
        if (in_array($this->status, [self::STATUS_PENDING, self::STATUS_IN_PROGRESS], true)) {
            $today = (new DateTime('today'))->format('Y-m-d');
            if ($this->due_date < $today) {
                $this->addError($attribute, 'Due date cannot be in the past for pending/in_progress.');
            }
        }
    }

    /**
     * @throws Exception
     */
    public function log(string $operation, array $changes = []): void
    {
        $userId = Yii::$app->user->id ?? null;
        $log = new TaskLog();
        $log->task_id   = $this->id;
        $log->user_id   = $userId;
        $log->operation = $operation;
        $log->changes   = $changes ? json_encode($changes, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) : null;
        $log->created_at= time();
        $log->save(false);
    }

    /**
     * @throws Exception
     */
    public function afterSave($insert, $changedAttributes): void
    {
        parent::afterSave($insert, $changedAttributes);
        if (!$insert && $changedAttributes) {
            $diff = [];
            foreach ($changedAttributes as $k => $old) {
                $diff[$k] = ['old' => $old, 'new' => $this->getAttribute($k)];
            }
            $this->log('update', $diff);
        }
    }

}
