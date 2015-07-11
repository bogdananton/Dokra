<?php
namespace Dokra\assets;

use Dokra\formats\WSDL\VersionChanges;

class VersionChangesList
{
    /**
     * @var \Dokra\formats\WSDL\VersionChanges
     */
    public $WSDL;

    public function __construct()
    {
        $this->WSDL = new VersionChanges();
    }
}
