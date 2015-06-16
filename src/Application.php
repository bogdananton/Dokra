<?php
namespace Dokra;

use Dokra\assets\APIFileEntry;
use Dokra\assets\ImporterInterface;
use Dokra\base\Disk;

class Application
{
    use base\RegistryT;

    protected $tasks = [];
    protected $interfaces;

    public function __construct()
    {
        $this->disk = new Disk();
        $this->importWSDL = new formats\WSDL\Importer();
        $this->importPHP = new formats\PHP\Importer();

        $this->differ = new \stdClass();
        $this->differ->wsdlVersionChanges = new formats\WSDL\VersionChanges();

        $this->interfaces = (object)[
            'wsdl' => [],
            'php' => []
        ];
    }

    public function run()
    {
        $this->indexProjectFiles();

        $this->interfaces->wsdl = $this->getWSDLs();
        $this->interfaces->php = $this->getPHPs();

        $this->processTasks();
    }

    protected function processTasks()
    {
        foreach ($this->tasks as $task) {
            switch ($task) {
                case 'output.cache':
                    $this->logCache('structure.wsdl.json', $this->interfaces);
                    break;

                case 'diff.wsdl':
                    $differWSDL = $this->differ->wsdlVersionChanges->from($this->interfaces->wsdl)->run();
                    $this->logCache('diff.wsdl.json', $differWSDL->getJSON());
                    $this->logCache('diff.wsdl.html', $differWSDL->getHTML(), false);
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

    protected function logCache($file, $data, $isJSON = true)
    {
        file_put_contents(
            $this->config()->get('cache.temporary') . '/' . $file,
            ($isJSON ? json_encode($data, JSON_PRETTY_PRINT) : $data)
        );
    }

    public function getWSDLs()
    {
        $files = $this->getFilesByExtension('wsdl');
        $this->config()->set('wsdl.files', $files);

        return $this->processFromFiles($files, $this->importWSDL);
    }

    public function getPHPs()
    {
        $files = $this->getFilesByExtension('php');
        $this->config()->set('php.files', $files);

        return $this->processFromFiles($files, $this->importPHP);
    }

    protected function processFromFile($filePath, ImporterInterface $importer)
    {
        foreach ($this->getFilePattern($importer) as $pattern => $matchingKeys) {
            preg_match($pattern, $filePath, $matches);

            if (!empty($matches)) {
                array_shift($matches);

                if (count($matches) == count($matchingKeys)) {
                    $version = $endpoint = null;

                    foreach ($matchingKeys as $index => $matchingKey) {
                        $$matchingKey = $matches[$index];
                    }

                    $endpoint = $this->transformEndpointName($endpoint);

                    $fileEntry = new APIFileEntry($importer::ID, $filePath, $version, $endpoint);
                    $interfaceEntry = $importer->convertFile($fileEntry);

                    return $interfaceEntry;
                }
            }
        }

        return false;
    }

    public function getFilePattern($importer)
    {
        return $this->config()->get('routing.regex.' . $importer::ID);
    }

    protected function processFromFiles($files, $importer)
    {
        $response = [];

        foreach ($files as $filePath) {
            if ($interface = $this->processFromFile($filePath, $importer)) {
                $response[] = $interface;
            }
        }

        return $response;
    }

    protected function getFilesByExtension($extension)
    {
        $allFiles = $this->config()->get('project.files');

        foreach ($allFiles as $index => $filePath) {
            if (Disk::getExtension($filePath) != $extension) {
                unset($allFiles[$index]);
            }
        }
        return $allFiles;
//        $filterFiles = array_filter($allFiles, function ($filePath) use ($extension) {
//            return (bool)preg_match('/\.' . $extension . '$/i', $filePath);
//        });
//        return $filterFiles;
    }

    protected function transformEndpointName($endpoint)
    {
        $transforms = $this->config()->get('routing.transform.endpoint');
        foreach ($transforms as $transform) {
            $endpoint = str_replace($transform[0], $transform[1], $endpoint);
        }

        return $endpoint;
    }
}
