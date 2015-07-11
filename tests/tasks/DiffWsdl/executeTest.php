<?php
namespace tests\tasks\DiffWsdl;


use Dokra\Application;
use Dokra\assets\VersionChangesList;
use Dokra\base\Registry;
use Dokra\tasks\DiffWsdl;
use Dokra\formats\WSDL\VersionChanges;

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
     * When called will extract JSON and HTML information and store them.
     */
    public function testWhenCalledWillExtractJSONAndHTMLInformationAndStoreThem()
    {
        $app = new Application();
        $interfaces = $app->getConfig(Application::INTERFACES);

        $getJSON = ['123'];
        $getHTML = 'html';

        $mockWSDLVersions = \Mockery::mock(VersionChanges::class)->makePartial();
        $mockWSDLVersions->shouldReceive('from')->once()->with($interfaces->WSDL)->andReturn($mockWSDLVersions);
        $mockWSDLVersions->shouldReceive('run')->once()->andReturn($mockWSDLVersions);
        $mockWSDLVersions->shouldReceive('getJSON')->once()->andReturn($getJSON);
        $mockWSDLVersions->shouldReceive('getHTML')->once()->andReturn($getHTML);

        $versionChangesList = new VersionChangesList();
        $versionChangesList->WSDL = $mockWSDLVersions;

        $storage = \Mockery::mock('FileStorage');
        $storage->shouldReceive('store')->once()->with(Application::DIFF_WSDL_JSON, $getJSON);
        $storage->shouldReceive('store')->once()->with(Application::DIFF_WSDL_HTML, $getHTML);

        $app->setConfig(Application::STORAGE, $storage);

        $worker = \Mockery::mock(DiffWsdl::class)->makePartial();
        $worker->shouldReceive('getConfig')->once()->with(Application::VERSION_CHANGES)->andReturn($versionChangesList);

        $response = $worker->execute($app);
        static::assertTrue($response);
    }
}