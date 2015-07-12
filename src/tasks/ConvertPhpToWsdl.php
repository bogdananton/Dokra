<?php
namespace Dokra\tasks;


use Dokra\Application;
use Dokra\base\Config;
use Dokra\base\Task;
use Dokra\exceptions\ContentException;
use Dokra\formats\PHP\Importer;
use Dokra\formats\WSDL\Exporter;

class ConvertPhpToWsdl extends Task
{
    use Config;

    public function execute(Application $app)
    {
        $importerErrors = Importer::getErrors();

        if (count($importerErrors) > 0) {
            $importerErrors = array_unique($importerErrors);
            throw new ContentException(implode(PHP_EOL, $importerErrors));
        }

        try {
            $this->processInterface($this->currentInterface());

        } catch (\Exception $e) {
            throw new ContentException('Can\'t find the requested endpoint. ' . $e->getMessage());
        }
    }

    protected function currentInterface()
    {
        $item = $this->getConfig(Application::FLASH_STORAGE_TASK);

        $e = $item->source->endpoint;
        $v = $item->source->version;

        $file = sprintf(Application::WSDL_ENDPOINT_OUTPUT, $e, $v, 'temporary.json');
        $this->getStorage()->set($file, $item);

        return $item;
    }

    protected function processInterface($item)
    {
        $exporter = new Exporter();
        $exporter->fromPHP($item)->run();
    }
}