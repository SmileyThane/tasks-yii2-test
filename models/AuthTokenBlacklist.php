<?php

namespace app\models;

use yii\db\ActiveRecord;

class AuthTokenBlacklist extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'auth_token_blacklist';
    }

    public static function isRevoked(string $jti): bool
    {
        return static::find()->where(['jti' => $jti])->exists();
    }
}
