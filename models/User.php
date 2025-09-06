<?php

namespace app\models;

use Yii;
use yii\base\Exception;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\web\IdentityInterface;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $role
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Task[] $tasks
 */
class User extends ActiveRecord implements IdentityInterface
{
    public static function tableName(): string
    {
        return 'users';
    }

    public function behaviors(): array
    {
        return [TimestampBehavior::class];
    }

    public function rules(): array
    {
        return [
            [['name', 'email'], 'required'],
            [['name', 'email', 'role'], 'string', 'max' => 255],
            [['email'], 'email'],
            [['email'], 'unique'],
        ];
    }

    public function getTasks(): ActiveQuery
    {
        return $this->hasMany(Task::class, ['assigned_to' => 'id']);
    }

    public static function findIdentity($id): User|IdentityInterface|null
    {
        return static::findOne((int)$id);
    }

    public static function findIdentityByAccessToken($token, $type = null): null
    {
        return null;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getAuthKey(): ?string
    {
        return $this->auth_key;
    }

    public function validateAuthKey($authKey): bool
    {
        return $this->auth_key === $authKey;
    }

    /**
     * @throws Exception
     */
    public function setPassword(string $password): void
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    public function validatePassword(string $password): bool
    {
        return $this->password_hash && Yii::$app->security->validatePassword($password, $this->password_hash);
    }
}
