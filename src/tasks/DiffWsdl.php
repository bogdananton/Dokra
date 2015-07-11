<?php
namespace Dokra\tasks;


use Dokra\Application;
use Dokra\assets\VersionChangesList;
use Dokra\base\Config;
use Dokra\base\Task;
use Dokra\formats\WSDL\VersionChanges;

class DiffWsdl extends Task
{
    use Config;

    public function execute(Application $app)
    {
        /** @var VersionChangesList */
        $versionChanges = $this->getConfig($app::VERSION_CHANGES);
        $interfaces = $this->getConfig($app::INTERFACES);

        /** @var VersionChanges $WSDL */
        $WSDL = $versionChanges->WSDL;

        /** @var VersionChanges $differWSDL */
        $WSDL->from($interfaces->WSDL)->run();

        $app->getStorage()->store(Application::DIFF_WSDL_JSON, $WSDL->getJSON());
        $app->getStorage()->store(Application::DIFF_WSDL_HTML, $WSDL->getHTML());

        return true;
    }
}