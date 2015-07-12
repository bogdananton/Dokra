<?php
namespace Dokra\tasks;


use Dokra\Application;
use Dokra\base\Config;
use Dokra\base\Task;
use Dokra\exceptions\SetupException;

class ScanFiles extends Task
{
    use Config;

    public function execute(Application $app)
    {
        $projectPath = $this->getProjectPath();
        $files = $this->getStorage()->files($projectPath);

        if (count($files) === 0) {
            throw new SetupException(sprintf($projectPath. 'No files were found, is this the right path [%s]?', $projectPath));
        }

        $this->setProjectFiles($files);
        return true;
    }
}
