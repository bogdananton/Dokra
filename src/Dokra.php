<?php
namespace Dokra;

class Dokra
{
    use base\RegistryT;

    protected $tasks = [];
    protected $interfaces;

    public function __construct()
    {
        $this->disk = new base\Disk;
        $this->importer = new import\Importer;

        $this->differ = new \stdClass();
        $this->differ->wsdlVersionChanges = new diff\WSDLVersionChanges();

        $this->interfaces = (object)[
            'wsdl' => [],
            'php' => []
        ];
    }

    public function run()
    {
        $this->indexProjectFiles();

        $this->interfaces->wsdl = $this->importer->getWSDLs();
        $this->interfaces->php = $this->importer->getPHPs();

        $this->processTasks();
    }

    protected function processTasks()
    {
        foreach ($this->tasks as $task) {
            switch ($task) {
                case 'output.cache':
                    $this->taskOutputCache();
                    break;

                case 'diff.wsdl':
                    $this->differ->wsdlVersionChanges->from($this->interfaces->wsdl)->run();
                    break;
            }
        }
    }

    public function registerTask($taskCode)
    {
        $this->tasks[] = $taskCode;
    }

    protected function indexProjectFiles()
    {
        // extract array of file paths for all PHP and WSDL files
        $projectPath = $this->config()->get('project.path');
        $files = $this->disk->getFiles($projectPath);

        $this->config()->set('project.files', $files);
    }

    protected function taskOutputCache()
    {
        file_put_contents(
            $this->config()->get('cache.temporary') . '/output.json',
            json_encode($this->interfaces, JSON_PRETTY_PRINT)
        );
    }
}
