<?php

namespace app\models;

use yii\base\InvalidConfigException;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * @property int $id
 * @property string $name
 * @property string|null $color
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Task[] $tasks
 */
class Tag extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'tags';
    }

    public function behaviors(): array
    {
        return [TimestampBehavior::class];
    }

    public function rules(): array
    {
        return [
            [['name'], 'required'],
            [['name', 'color'], 'string', 'max' => 255],
            [['name'], 'unique'],
        ];
    }

    /**
     * @throws InvalidConfigException
     */
    public function getTasks(): ActiveQuery
    {
        return $this->hasMany(Task::class, ['id' => 'task_id'])
            ->viaTable('tasks_tags', ['tag_id' => 'id']);
    }
}
