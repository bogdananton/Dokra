<?php
namespace Dokra\assets;

class PHPReturn
{
    public $type;
    public $details;

    public function __construct($raw)
    {
        $raw = str_replace("\t", " ", $raw);
        $examine = explode(" ", $raw);
        $examine = array_filter($examine);
        
        $this->type = array_shift($examine);

        if (!empty($examine)) {
            $this->details = implode(" ", $examine);
        }
    }
}
