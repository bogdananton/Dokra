<?php
namespace tests\Application;

use \Dokra\Application;
use Dokra\base\Task;

class runTest extends \PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        \Mockery::close();
    }

    /**
     * When no task is queued then return false.
     */
    public function testWhenNoTaskIsQueuedThenReturnFalse()
    {
        $app = new Application();
        $this->assertFalse($app->run());
    }

    /**
     * When the config is initialized then scan for files, extract entries and run tasks.
     */
    public function testWhenTheConfigIsInitializedThenScanForFilesExtractEntriesAndRunTasks()
    {
        /** @var Application|\Mockery\Mock $app */
        $app = \Mockery::mock(Application::class)->makePartial();
        $app->setConfig(Application::PROJECT_PATH, __DIR__ . '/../../');
        $app->setConfig(Application::CACHE_TEMPORARY, __DIR__ . '/../../cache/');

        $app->addTask(Task::SCAN_FILES);
        $response = $app->run();

        $this->assertTrue($response);
    }
}
