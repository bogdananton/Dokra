<?php
date_default_timezone_set('Europe/Bucharest');
$time = time() + microtime();

require_once __DIR__ . '/../vendor/autoload.php';

use \Dokra\base\Task as TaskManager;

$app = new \Dokra\Application();

$configuration = require_once 'configuration.php';
$app->initConfig($configuration);

$app->addTask(TaskManager::SCAN_FILES);
$app->addTask(TaskManager::IMPORT_INTERFACES);
$app->addTask(TaskManager::OUTPUT_CACHE);
$app->addTask(TaskManager::DIFF_WSDL);

// @todo fill this with version differ and serialization differ

$app->run();

echo "\n\nGenerated in " . number_format(time() + microtime() - $time, 2) . "s\n\n";
