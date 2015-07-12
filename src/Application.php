<?php
namespace Dokra;

use Dokra\assets\InterfacesList;
use Dokra\assets\VersionChangesList;
use Dokra\storage\FileStorage;
use Dokra\base\Task;
use Dokra\base\Config;

class Application
{
    const CACHE_TEMPORARY = 'cache.temporary';
    const PROJECT_PATH = 'project.path';
    const PROJECT_FILES = 'project.files';
    const ROUTING_REGEX_WSDL = 'routing.regex.wsdl';
    const ROUTING_REGEX_PHP = 'routing.regex.php';
    const ROUTING_TRANSFORM_ENDPOINT = 'routing.transform.endpoint';

    const INTERFACES = 'interfaces';
    const VERSION_CHANGES = 'versionChanges';
    const STORAGE = 'storage';
    const FLASH_STORAGE_TASK = 'flash.storage.task';

    const FLAG_USE_REFLECTION = 'flag.use.reflection.to.extract.php.assets';

    const STRUCTURE_WSDL_JSON = 'structure.wsdl.json';
    const DIFF_WSDL_JSON = 'diff.wsdl.json';
    const DIFF_WSDL_HTML = 'diff.wsdl.html';
    const CACHE_FILES_JSON = 'cache.files.json';

    use Config;

    protected $tasks = [];
    protected $storage;

    public function __construct()
    {
        $this->setConfig(self::VERSION_CHANGES, new VersionChangesList());
        $this->setConfig(self::INTERFACES, new InterfacesList());
        $this->setConfig(self::STORAGE, new FileStorage());
    }

    public function addTask($taskCode)
    {
        $this->tasks[] = $taskCode;
        return $this;
    }

    /**
     * @return bool if at least one task was performed.
     */
    public function run()
    {
        $response = false;
        $tasks = $this->tasks;
        $this->tasks = [];

        foreach ($tasks as $task) {
            $instance = Task::getInstance($task);
            if ($instance instanceof Task) {
                $instance->execute($this);
                $response = true;
            }
        }

        return $response;
    }
}
