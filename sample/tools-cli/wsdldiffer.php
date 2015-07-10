<?php
// @todo fill this with version differ and serialization differ

$time = time() + microtime();
require_once '../../vendor/autoload.php';

$worker = new \Dokra\Application();

$worker->config()->set('project.path', __DIR__ . '/../application');
$worker->config()->set('cache.temporary', __DIR__ . '/../../cache');

$worker->config()->set('routing.regex.wsdl', [
    '/\/soap-rpc\/([\w\-]+)\/([\w\-]+)\-([\d\.]+)\.wsdl$/' => ['endpoint', 'endpoint', 'version']
]);

$worker->config()->set('routing.transform.endpoint', [
   ['_full', '']
]);

$worker->config()->set('routing.regex.php', [
    '/\/v([\d\.]+)\/([\w]+)\/API.class.php$/' => ['version', 'endpoint']
]);

$worker->registerTask('output.cache');
$worker->registerTask('diff.wsdl');
$worker->run();

echo "\n\nGenerated in " . number_format(time() + microtime() - $time, 2) . "s\n\n";

