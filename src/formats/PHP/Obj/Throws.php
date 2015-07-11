<?php
namespace Dokra\formats\PHP\Obj;


class Throws
{
    public $exception;
    public $details;

    public function __construct($raw)
    {
        $raw = str_replace("\t", " ", $raw);
        $examine = explode(" ", $raw);
        $examine = array_filter($examine);
        
        $this->exception = array_shift($examine);

        if (!empty($examine)) {
            $this->details = implode(" ", $examine);
        }
    }
}
