<?php
namespace tests\tasks\OutputCache;

use Dokra\Application;
use Dokra\base\Registry;
use Dokra\storage\FileStorage;
use Dokra\tasks\OutputCache;

class executeTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $config = Registry::getInstance();
        $config->reset();
    }

    public function tearDown()
    {
        \Mockery::close();
    }

    /**
     * When called will store the interfaces information.
     */
    public function testWhenCalledWillStoreTheInterfacesInformation()
    {
        $app = new Application();
        $interfaces = $app->getConfig(Application::INTERFACES);

        $storage = \Mockery::mock(FileStorage::class)->makePartial();
        $storage->shouldReceive('set')->once()->with(Application::STRUCTURE_WSDL_JSON, $interfaces);

        $app->setConfig(Application::STORAGE, $storage);

        $worker = \Mockery::mock(OutputCache::class)->makePartial();
        $response = $worker->execute($app);

        static::assertTrue($response);
    }
}
