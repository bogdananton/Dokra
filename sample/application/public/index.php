<?php
if (!file_exists('../vendor/autoload.php')) {
    die('run "composer update" in application-1\'s root folder!');
}

require_once('../vendor/autoload.php');

$app = new DokraApplication\Main();

try {
    $app->run();
    
} catch (\Exception $e) {
    $error = new \stdClass;
    $error->type = 'Exception';
    $error->message = $e->getMessage();
    echo json_encode($error);
}
