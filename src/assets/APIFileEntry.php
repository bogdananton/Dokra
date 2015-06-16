<?php
namespace Dokra\assets;

class APIFileEntry
{
    public $type;
    public $filePath;
    public $version;
    public $endpoint;

    public function __construct($type, $filePath, $version, $endpoint)
    {
        $this->type = strtoupper($type);
        $this->filePath = $filePath;
        $this->version = $version;
        $this->endpoint = strtolower($endpoint);
    }
}
