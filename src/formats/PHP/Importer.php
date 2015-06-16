<?php
namespace Dokra\formats\PHP;

use Dokra\assets\APIFileEntry;
use Dokra\assets\ImporterInterface;
use Dokra\assets\PHPEntry;

class Importer implements ImporterInterface
{
    const ID = 'php';

    public function convertFile(APIFileEntry $interfaceFileEntry)
    {
        $response = new \stdClass();
        $response->source = $interfaceFileEntry;
        $response->entry = new PHPEntry($interfaceFileEntry->filePath);
        return $response;
    }
}