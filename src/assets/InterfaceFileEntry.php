<?php
namespace Dokra\assets;

class InterfaceFileEntry
{
    public $type;
    public $filePath;
    public $version;
    public $endpoint;

    public function __construct($type, $filePath, $version, $endpoint)
    {
        $this->type = strtoupper($type);
        $this->filePath = $filePath;
        $this->version = $version; // @todo remove float cast in order to support minor versions
        $this->endpoint = strtolower($endpoint);
    }
}
