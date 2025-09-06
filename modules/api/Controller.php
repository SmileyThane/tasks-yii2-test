<?php
namespace app\modules\api;

use yii\rest\Controller as RestController;
use yii\filters\ContentNegotiator;
use yii\filters\Cors;
use yii\rest\Serializer;
use yii\web\Response;
use app\components\JwtHttpBearerAuth;
use yii\filters\AccessControl;

class Controller extends RestController
{
    public $serializer = [
        'class' => Serializer::class,
        'collectionEnvelope' => 'items',
    ];

    public function behaviors(): array
    {
        $b = parent::behaviors();

        $b['contentNegotiator'] = [
            'class' => ContentNegotiator::class,
            'formats' => ['application/json' => Response::FORMAT_JSON],
        ];

        $b['cors'] = [
            'class' => Cors::class,
            'cors' => [
                'Origin' => ['*'],
                'Access-Control-Request-Method' => ['GET','POST','PUT','PATCH','DELETE','OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Max-Age' => 86400,
            ],
        ];

        $b['authenticator'] = ['class' => JwtHttpBearerAuth::class];

        $b['access'] = [
            'class' => AccessControl::class,
            'rules' => [
                ['allow' => true, 'roles' => ['@']],
            ],
        ];

        return $b;
    }
}
