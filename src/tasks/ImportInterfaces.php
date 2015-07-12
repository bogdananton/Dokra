<?php
namespace Dokra\tasks;


use Dokra\Application;
use Dokra\assets\APIFileEntry;
use Dokra\base\Importer;
use Dokra\exceptions\SetupException;
use Dokra\storage\FileStorage;
use Dokra\base\Config;
use Dokra\base\Task;

class ImportInterfaces extends Task
{
    protected $app;
    protected $importers;
    protected $serializationFormats = ['PHP', 'WSDL'];
    protected $requiredFields = ['version', 'endpoint'];

    use Config;

    public function execute(Application $app)
    {
        $this->app = $app;
        $this->importers = Importer::getInstances();
        $interfaces = $app->getConfig(Application::INTERFACES);

        foreach ($this->serializationFormats as $format) {
            $interfaces->{$format} = $this->interfaces($format);
        }

        $app->setConfig(Application::INTERFACES, $interfaces);
    }

    public function interfaces($type)
    {
        $response = [];

        $type = strtolower($type);
        $uType = strtoupper($type);

        if (in_array($uType, $this->serializationFormats, false)) {
            foreach ($this->files($type) as $filePath) {
                $response[] = $this->mapInterface($filePath, $this->importers->{$uType});
            }
        }

        return array_values(array_filter($response));
    }

    protected function mapInterface($filePath, Importer $importer)
    {
        if ($match = $this->detectEndpoint($filePath, $importer)) {
            $this->transformEndpointName($match->endpoint);

            return $importer->convertFile(new APIFileEntry(
                $importer->getId(),
                $filePath,
                $match->version,
                $match->endpoint
            ));
        }

        return false;
    }

    protected function detectEndpoint($filePath, Importer $importer)
    {
        $patterns = $this->getConfig('routing.regex.' . $importer->getId());

        foreach ($patterns as $pattern => $matchingKeys) {
            preg_match($pattern, $filePath, $matches);

            if (null !== $matches && count($matches) > 1) {
                array_shift($matches);
                if (count($matches) === count($matchingKeys)) {
                    $response = new \stdClass();

                    foreach ($matchingKeys as $index => $matchingKey) {
                        $response->{$matchingKey} = $matches[$index];
                    }

                    $missingFields = array_diff($this->requiredFields, array_keys(get_object_vars($response)));
                    if (count($missingFields) > 0) {
                        throw new SetupException('Some fields are missing from [' . $filePath . ']: [' . implode(', ', $missingFields) . '].');
                    }

                    return $response;
                }
            }
        }
    }

    protected function files($extension)
    {
        $allFiles = $this->getConfig(Application::PROJECT_FILES);

        if (count($allFiles) > 0) {
            foreach ($allFiles as $index => $filePath) {
                if (FileStorage::extension($filePath) !== $extension) {
                    unset($allFiles[$index]);
                }
            }
        }

        return $allFiles;
    }

    protected function transformEndpointName(&$endpoint)
    {
        $transforms = $this->getConfig(Application::ROUTING_TRANSFORM_ENDPOINT);

        foreach ($transforms as $transform) {
            $endpoint = str_replace($transform[0], $transform[1], (string)$endpoint);
        }
    }
}
