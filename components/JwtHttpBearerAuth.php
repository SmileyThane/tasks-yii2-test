<?php
namespace app\components;

use app\models\AuthTokenBlacklist;
use app\models\User;
use Throwable;
use Yii;
use yii\filters\auth\AuthMethod;
use yii\web\IdentityInterface;
use yii\web\UnauthorizedHttpException;

class JwtHttpBearerAuth extends AuthMethod
{
    public function authenticate($user, $request, $response): User|IdentityInterface|null
    {
        $auth = $request->getHeaders()->get('Authorization');
        if (!$auth || stripos($auth, 'bearer ') !== 0) return null;

        $token = trim(substr($auth, 7));
        try {
            $payload = JwtAuth::parse($token);
        } catch (Throwable) {
            throw new UnauthorizedHttpException('Invalid token.');
        }

        if (!empty($payload['jti']) && AuthTokenBlacklist::isRevoked($payload['jti'])) {
            throw new UnauthorizedHttpException('Token revoked.');
        }

        $identity = User::findOne((int)$payload['sub']);
        if (!$identity) {
            throw new UnauthorizedHttpException('User not found.');
        }
        $tv = (int)($payload['tv'] ?? -1);
        if ($tv !== (int)$identity->token_version) {
            throw new UnauthorizedHttpException('Token invalidated.');
        }

        $user->setIdentity($identity);
        Yii::$app->params['jwt_payload'] = $payload;
        return $identity;
    }
}
