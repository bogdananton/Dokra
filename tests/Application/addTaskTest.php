<?php
namespace tests\Application;

use Dokra\Application;

class addTask extends \PHPUnit_Framework_TestCase
{
    /**
     * When the config is initialized then scan for files, extract entries and run tasks.
     */
    public function testWhenTheConfigIsInitializedThenScanForFilesExtractEntriesAndRunTasks()
    {
        $app = new Application();

        $app->addTask('task.code.1');
        $app->addTask('task.code.2');
        $app->addTask('task.code.3');
        $app->addTask('task.code.4');

        $expected = [
            'task.code.1',
            'task.code.2',
            'task.code.3',
            'task.code.4'
        ];

        $helper = new \PHPUnitProtectedHelper($app);
        $this->assertEquals($expected, $helper->getValue('tasks'));
    }

    /**
     * By default, two tasks are set to initialize the application.
     */
    public function testByDefaultTwoTasksAreSetToInitializeTheApplication()
    {
        $app = new Application();

        $helper = new \PHPUnitProtectedHelper($app);
        $this->assertEquals([], $helper->getValue('tasks'));
    }
}
