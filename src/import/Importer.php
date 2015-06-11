<?php
namespace Dokra\import;

use Dokra\assets\InterfaceFileEntry;
use Dokra\base\RegistryT;
use Dokra\base\Disk;
use Dokra\import\from\FromInterface;
use Dokra\import\from\PHP;
use Dokra\import\from\WSDL;

class Importer
{
    use RegistryT;

    public function __construct()
    {
        $this->importWSDL = new WSDL();
        $this->importPHP = new PHP();
    }

    public function getWSDLs()
    {
        $files = $this->getFilesByExtension('wsdl');
        return $this->processFromFiles($files, $this->importWSDL);
    }

    public function getPHPs()
    {
        $files = $this->getFilesByExtension('php');
        return $this->processFromFiles($files, $this->importPHP);
    }

    protected function processFromFile($filePath, FromInterface $importer)
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

                    $fileEntry = new InterfaceFileEntry($importer::ID, $filePath, $version, $endpoint);
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
