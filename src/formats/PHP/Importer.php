<?php
namespace Dokra\formats\PHP;

use Dokra\assets\APIFileEntry;
use Dokra\assets\PHPEntry;
use Dokra\base\Importer as ImporterA;

class Importer extends ImporterA
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