<?php

namespace app\modules\api\controllers;

use app\components\JwtAuth;
use app\models\AuthTokenBlacklist;
use app\models\User;
use app\modules\api\Controller;
use Random\RandomException;
use Yii;
use yii\base\InvalidConfigException;
use yii\db\Exception;
use yii\filters\AccessControl;

class AuthController extends Controller
{
    public function behaviors(): array
    {
        $b = parent::behaviors();

        if (isset($b['authenticator'])) {
            $b['authenticator']['only'] = ['logout'];
        }

        $b['access'] = [
            'class' => AccessControl::class,
            'only' => ['login', 'logout'],
            'rules' => [
                [
                    'actions' => ['login'],
                    'allow' => true,
                    'roles' => ['?'],
                ],
                [
                    'actions' => ['logout'],
                    'allow' => true,
                    'roles' => ['@'],
                ],
            ],
        ];

        return $b;
    }

    /**
     * @throws RandomException
     * @throws InvalidConfigException
     */
    public function actionLogin(): array
    {
        $body = Yii::$app->request->getBodyParams();
        $email = trim((string)($body['email'] ?? ''));
        $password = (string)($body['password'] ?? '');

        /** @var User $user */
        $user = User::find()->where(['email' => $email])->one();
        if (!$user || !$user->validatePassword($password)) {
            Yii::$app->response->statusCode = 401;
            return ['error' => 'Invalid credentials'];
        }

        $token = JwtAuth::issue($user->id, $user->role ?? 'user', (int)$user->token_version);
        return [
            'token_type' => 'Bearer',
            'access_token' => $token,
            'expires_in' => Yii::$app->params['jwt']['ttl'],
        ];
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function actionLogout(): array
    {
        $user = Yii::$app->user->identity;
        $body = Yii::$app->request->getBodyParams();
        $all = filter_var($body['all'] ?? Yii::$app->request->get('all'), FILTER_VALIDATE_BOOL);

        if ($all) {
            $user->updateCounters(['token_version' => 1]);
            return ['ok' => true, 'all' => true];
        }

        $payload = Yii::$app->params['jwt_payload'] ?? null;
        if (!$payload || empty($payload['jti']) || empty($payload['exp'])) {
            $user->updateCounters(['token_version' => 1]);
            return ['ok' => true, 'all' => true];
        }

        if (!AuthTokenBlacklist::isRevoked($payload['jti'])) {
            $bl = new AuthTokenBlacklist();
            $bl->jti = (string)$payload['jti'];
            $bl->user_id = $user->id;
            $bl->exp = (int)$payload['exp'];
            $bl->created_at = time();
            $bl->save(false);
        }

        return ['ok' => true, 'all' => false];
    }
}
