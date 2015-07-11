<?php
date_default_timezone_set('Europe/Bucharest');
$time = time() + microtime();

require_once '../../vendor/autoload.php';

use \Dokra\base\Task as TaskManager;
use \Dokra\Application;

$app = new Application();

$configuration = require_once 'configuration.php';
$app->initConfig($configuration);

// change this to false or disable it to use file contents scraping.
// Useful when the process fails because of custom autoloader or complex loading logic.
$app->setConfig(Application::FLAG_USE_REFLECTION, true);

$app->addTask(TaskManager::SCAN_FILES);
$app->addTask(TaskManager::IMPORT_INTERFACES);
$app->addTask(TaskManager::OUTPUT_CACHE);

$app->run();

$interfaces = [];

$desiredVersion = '1';
$endpoint = 'element';

try {
    $interfaces = $app->getStorage()->get(Application::STRUCTURE_WSDL_JSON);
} catch (Exception $e) {
    echo('Failed: Can\'t extract the PHP interfaces.' . "\n");
}

try {
    if (isset($interfaces->PHP) && count($interfaces->PHP) > 0) {
        foreach ($interfaces->PHP as $item) {
            if ($item->source->version === $desiredVersion && $item->source->endpoint === $endpoint) {
                $app->setConfig(Application::FLASH_STORAGE_TASK, $item);
                $app->addTask(TaskManager::CONVERT_PHP_TO_WSDL);
                $app->run();
            }
        }
    }
} catch (\Exception $e) {
    echo('Failed: couldn\'t convert the endpoint to WSDL because:' . PHP_EOL . $e->getMessage());
}

echo "\n\nGenerated in " . number_format(time() + microtime() - $time, 2) . "s\n\n";
