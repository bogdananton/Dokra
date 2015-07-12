<?php
namespace Dokra\base;

use Dokra\assets\APIFileEntry;
use Dokra\assets\ImportersList;
use Dokra\formats\WSDL\Importer as WSDLImporter;
use Dokra\formats\PHP\Importer as PHPImporter;

abstract class Importer
{
    /**
     * @return ImportersList
     */
    public static function getInstances()
    {
        $response = new ImportersList();
        $response->WSDL = new WSDLImporter();
        $response->PHP = new PHPImporter();

        return $response;
    }

    /**
     * @return string Importer identifier, lowercase extension / format.
     */
    abstract public function getId();

    abstract public function convertFile(APIFileEntry $interfaceFileEntry);
}
