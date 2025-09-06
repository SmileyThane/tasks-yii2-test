<?php
namespace app\components;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Random\RandomException;
use Yii;

class JwtAuth
{
    /**
     * @throws RandomException
     */
    public static function issue(int $userId, string $role, int $tokenVersion): string
    {
        $p = Yii::$app->params['jwt'];
        $now = time();
        $payload = [
            'iss' => $p['issuer'],
            'aud' => $p['aud'],
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + (int)$p['ttl'],
            'sub' => $userId,
            'role'=> $role,
            'jti' => bin2hex(random_bytes(16)),
            'tv'  => $tokenVersion,
        ];
        return JWT::encode($payload, $p['key'], 'HS256');
    }


    public static function parse(string $token): array
    {
        $p = Yii::$app->params['jwt'];
        $decoded = JWT::decode($token, new Key($p['key'], 'HS256'));
        return (array)$decoded;
    }
}
