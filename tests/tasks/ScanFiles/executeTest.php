<?php
namespace tests\tasks\ScanFiles;

use Dokra\Application;
use Dokra\base\Registry;
use Dokra\base\Task;
use Dokra\tasks\ScanFiles;

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
     * When the project path configuration is not set then throw exception.
     * @expectedExceptionMessage Entry project.path not set.
     * @expectedException \Dokra\exceptions\SetupException
     */
    public function testWhenTheProjectPathConfigurationIsNotSetThenThrowException()
    {
        $app = new Application();
        $app->addTask(Task::SCAN_FILES);
        $app->run();
    }

    /**
     * When no files are found then throw exception.
     * @expectedExceptionMessage No files were found, is this the right path [/project/Dokra/sample/application]?
     * @expectedException \Dokra\exceptions\SetupException
     */
    public function testWhenNoFilesAreFoundThenThrowException()
    {
        $applicationPath = '/project/Dokra/sample/application';

        $storage = \Mockery::mock('FileStorage')->makePartial();
        $storage->shouldReceive('getFiles')->with($applicationPath)->once()->andReturn([]);

        /** @var \Dokra\Application $app */
        $app = \Mockery::mock(Application::class)->makePartial();

        $app->setConfig(Application::STORAGE, $storage);
        $app->setConfig(Application::PROJECT_PATH, $applicationPath);

        $app->addTask(Task::SCAN_FILES);
        $app->run();
    }
    /**
     * When files are found then store them.
     */
    public function testWhenFilesAreFoundThenStoreThem()
    {
        $extractedFiles = ['file1.php', 'file2.php'];
        $applicationPath = '/project/Dokra/sample/application';

        $storage = \Mockery::mock('FileStorage')->makePartial();
        $storage->shouldReceive('getFiles')
            ->with($applicationPath)
            ->once()
            ->andReturn($extractedFiles);

        /** @var \Dokra\Application $app */
        $app = \Mockery::mock(Application::class)->makePartial();

        $app->setConfig(Application::STORAGE, $storage);
        $app->setConfig(Application::PROJECT_PATH, $applicationPath);


        // initial setup: no files
        try {
            $app->getConfig(Application::PROJECT_FILES);
            static::fail('no files should have been set. check the reset.');

        } catch (\Exception $e) {
            // ok, no files
        }

        $worker = new ScanFiles();
        $response = $worker->execute($app);

        // expecting files to be stored
        $files = $app->getConfig(Application::PROJECT_FILES);

        static::assertEquals($extractedFiles, $files);
        static::assertTrue($response);
    }
}
