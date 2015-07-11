<?php
namespace tests\Application;


use Dokra\Application;
use Dokra\assets\InterfacesList;
use Dokra\assets\VersionChangesList;
use Dokra\storage\FileStorage;

class ConstructTest extends \PHPUnit_Framework_TestCase
{
    /**
     * class exists
     */
    public function testClassExists()
    {
        $this->assertTrue(class_exists('\Dokra\Application'));
    }

    /**
     * will set the common components.
     */
    public function testWillSetTheCommonComponents()
    {
        $app = new Application();

        $componentVersionChangesList = $app->getConfig(Application::VERSION_CHANGES);
        $componentInterfacesList = $app->getConfig(Application::INTERFACES);
        $componentDisk = $app->getConfig(Application::STORAGE);

        $this->assertInstanceOf(VersionChangesList::class, $componentVersionChangesList);
        $this->assertInstanceOf(InterfacesList::class, $componentInterfacesList);
        $this->assertInstanceOf(FileStorage::class, $componentDisk);

    }
}
