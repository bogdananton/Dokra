<?php
namespace Dokra\tasks;


use Dokra\Application;
use Dokra\assets\APIFileEntry;
use Dokra\base\Importer;
use Dokra\storage\FileStorage;
use Dokra\base\Config;
use Dokra\base\Task;

class ImportInterfaces extends Task
{
    protected $app;
    protected $importers;

    use Config;

    public function execute(Application $app)
    {
        $this->app = $app;

        $this->importers = Importer::getInstances();

        $interfaces = $app->getConfig($app::INTERFACES);
        $interfaces->WSDL = $this->getWSDLs();
        $interfaces->PHP = $this->getPHPs();

        $app->setConfig($app::INTERFACES, $interfaces);
    }

    public function getWSDLs()
    {
        $files = $this->getFilesByExtension('wsdl');
        $this->setConfig('wsdl.files', $files);

        return $this->processFromFiles($files, $this->importers->WSDL);
    }

    public function getPHPs()
    {
        $files = $this->getFilesByExtension('php');
        $this->setConfig('php.files', $files);

        return $this->processFromFiles($files, $this->importers->PHP);
    }

    protected function processFromFile($filePath, Importer $importer)
    {
        foreach ($this->getFilePattern($importer) as $pattern => $matchingKeys) {
            preg_match($pattern, $filePath, $matches);

            if (!empty($matches)) {
                array_shift($matches);

                if (count($matches) === count($matchingKeys)) {
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
        return $this->getConfig('routing.regex.' . $importer::ID);
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
        $allFiles = $this->getConfig(Application::PROJECT_FILES);

        foreach ($allFiles as $index => $filePath) {
            if (FileStorage::getExtension($filePath) != $extension) {
                unset($allFiles[$index]);
            }
        }
        return $allFiles;
    }

    protected function transformEndpointName($endpoint)
    {
        $transforms = $this->getConfig(Application::ROUTING_TRANSFORM_ENDPOINT);
        foreach ($transforms as $transform) {
            $endpoint = str_replace($transform[0], $transform[1], $endpoint);
        }

        return $endpoint;
    }
}
