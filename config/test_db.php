<?php
$db = require __DIR__ . '/db.php';
// test database! Important not to run tests on production or development databases
$db['dsn'] = $_ENV['DB_DSN_TEST'];

return $db;
