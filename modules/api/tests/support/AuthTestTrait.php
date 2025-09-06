<?php
declare(strict_types=1);

namespace app\modules\api\tests\support;

use app\components\JwtAuth;
use app\models\Tag;
use app\models\User;
use Yii;

trait AuthTestTrait
{
    protected ?int $adminId = null;
    protected ?string $adminToken = null;
    protected array $tagIds = [];

    protected function ensureAdminAndTags(): void
    {
        $admin = User::find()->where(['email' => 'admin@example.com'])->one();
        if (!$admin) {
            $admin = new User([
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'role' => 'admin',
                'created_at' => time(),
                'updated_at' => time(),
            ]);
            $admin->setPassword('admin123');
            $admin->save(false);
        }
        $this->adminId = (int)$admin->id;
        $this->adminToken = JwtAuth::issue($admin->id, $admin->role ?? 'admin', (int)($admin->token_version ?? 0));

        if (!Tag::find()->exists()) {
            foreach (['Bug', 'Feature', 'Docs'] as $name) {
                (new Tag([
                    'name' => $name,
                    'color' => sprintf('#%06X', mt_rand(0, 0xFFFFFF)),
                    'created_at' => time(), 'updated_at' => time(),
                ]))->save(false);
            }
        }
        $this->tagIds = Tag::find()->select('id')->column();
    }

    protected function swapRequest(?string $token = null, array $query = [], ?array $body = null): TestRequest
    {
        /** @var TestRequest $req */
        $req = new TestRequest([
            'enableCsrfCookie' => false,
            'parsers' => ['application/json' => \yii\web\JsonParser::class],
        ]);
        $req->setQueryParams($query);
        $req->setAuthBearer($token);
        if ($body !== null) {
            $req->getHeaders()->set('Content-Type', 'application/json');
            $req->setBodyParams($body);
        }
        Yii::$app->set('request', $req);
        return $req;
    }
}