<?php
namespace Dokra\base;


use Dokra\Application;

abstract class Task
{
    const SCAN_FILES = 'ScanFiles';
    const IMPORT_INTERFACES = 'ImportInterfaces';
    const OUTPUT_CACHE = 'OutputCache';
    const DIFF_WSDL = 'DiffWsdl';
    const CONVERT_PHP_TO_WSDL = 'ConvertPhpToWsdl';

    /**
     * @param string $task
     *
     * @return Task
     */
    public static function getInstance($task)
    {
        $rClass = new \ReflectionClass(self::class);
        $constants = $rClass->getConstants();

        if (in_array($task, $constants, true)) {
            $class = '\\Dokra\\tasks\\' . $task;
            if (class_exists($class)) {
                return new $class();
            }
        }
    }

    abstract public function execute(Application $app);
}
