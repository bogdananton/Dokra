<?php
namespace Dokra\tasks;


use Dokra\Application;
use Dokra\base\Config;
use Dokra\base\Task;

class OutputCache extends Task
{
    use Config;

    public function execute(Application $app)
    {
        $interfaces = $this->getConfig(Application::INTERFACES);
        $this->getStorage()->store(Application::STRUCTURE_WSDL_JSON, $interfaces);
        return true;
    }
}
