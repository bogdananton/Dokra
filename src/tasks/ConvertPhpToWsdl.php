<?php
namespace Dokra\tasks;


use Dokra\Application;
use Dokra\base\Config;
use Dokra\base\Task;
use Dokra\exceptions\ContentException;
use Dokra\formats\PHP\Importer;

class ConvertPhpToWsdl extends Task
{
    use Config;

    public function execute(Application $app)
    {
        $importerErrors = Importer::getErrors();

        if (!empty($importerErrors)) {
            $importerErrors = array_unique($importerErrors);
            throw new ContentException(implode(PHP_EOL, $importerErrors));
        }

        $item = $this->getConfig(Application::FLASH_STORAGE_TASK);

        $file = 'php2wsdl-endpoint[' . $item->source->endpoint . ']-version[' . $item->source->version . '].json';
        $this->getStorage()->set($file, $item);

        // @todo create exporter
    }
}