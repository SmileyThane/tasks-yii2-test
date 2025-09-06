<?php

use app\modules\api\Module;

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'modules' => [
        'api' => [
            'class' => Module::class,
        ],
    ],
    'components' => [
        'request' => [
            'cookieValidationKey' => $_ENV['COOKIE_VALIDATION_KEY'],
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ]
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@app/mail',
            // send all mails to a file by default.
            'useFileTransport' => true,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                'POST api/auth/login'  => 'api/auth/login',
                'POST api/auth/logout' => 'api/auth/logout',

                'GET api/tasks'                          => 'api/task/index',
                'GET api/tasks/<id:\d+>'                 => 'api/task/view',
                'POST api/tasks'                         => 'api/task/create',
                'PUT api/tasks/<id:\d+>'                 => 'api/task/update',
                'PATCH api/tasks/<id:\d+>/toggle-status' => 'api/task/toggle-status',
                'DELETE api/tasks/<id:\d+>'              => 'api/task/delete',
                'PATCH api/tasks/<id:\d+>/restore'       => 'api/task/restore',

                'GET api/tags'               => 'api/tag/index',
                'POST api/tags'              => 'api/tag/create',
                'PUT api/tags/<id:\d+>'      => 'api/tag/update',
                'DELETE api/tags/<id:\d+>'   => 'api/tag/delete',

                'GET test-spa' => 'site/test-spa'
            ],
        ],

    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}

return $config;
