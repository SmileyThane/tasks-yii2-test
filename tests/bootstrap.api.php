<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$consoleConfig = require __DIR__ . '/../config/console.php';
$consoleConfig['components']['db'] = require __DIR__ . '/../config/test_db.php';

$consoleApp = new yii\console\Application($consoleConfig);
$run = static function (string $route, array $params = []) {
    return Yii::$app->runAction($route, $params);
};

$code = $run('migrate/down', ['all']);
if ($code !== 0) {
    fwrite(STDERR, "[api-tests] truncate failed with code {$code}\n");
} else {
    fwrite(STDERR, "[api-tests] all tables has been removed.\n");
}


$code = $run('migrate/up', ['interactive' => 0]);
if ($code !== 0) {
    fwrite(STDERR, "[api-tests] migrate failed with code {$code}\n");
    exit($code);
}

$code = $run('seed/all', ['users' => 5, 'tags' => 15, 'tasks' => 50, 'soft' => 5]);
if ($code !== 0) {
    fwrite(STDERR, "[api-tests] seed failed with code {$code}\n");
    exit($code);
}

Yii::$app = null;

$config = require __DIR__ . '/../config/web.php';
$config['components']['db'] = require __DIR__ . '/../config/test_db.php';

new yii\web\Application($config);

require __DIR__ . '/../modules/api/tests/support/TestRequest.php';
require __DIR__ . '/../modules/api/tests/support/AuthTestTrait.php';

$tables = ['users', 'tags', 'tasks', 'tasks_tags'];
foreach ($tables as $tbl) {
    if (!Yii::$app->db->schema->getTableSchema($tbl, true)) {
        fwrite(STDERR, "[tests] Missing table `$tbl`. Run: php yii migrate --interactive=0\n");
        exit(1);
    }
}