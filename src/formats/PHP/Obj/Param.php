<?php
namespace Dokra\formats\PHP\Obj;


class Param
{
    // public $signature;
    public $type;
    public $name;
    public $details;
    public $default;

    public function __construct($raw, $signature)
    {
        $raw = str_replace("\t", " ", $raw);
        $examine = explode(" ", $raw);
        $examine = array_filter($examine);

        $this->type = array_shift($examine);

        if (!empty($examine)) {
            $this->name = array_shift($examine);
            if (substr($this->name, 0, 1)) {
                $this->name = substr($this->name, 1);
            }

            if (!empty($examine)) {
                $this->details = implode(" ", $examine);
            }
        }


        if (isset($signature->params) && !empty($this->name)) {
            if (isset($signature->params[$this->name])) {
                $this->default = $signature->params[$this->name];
            }
        }
    }

    public static function getInstance($rawParam, $signature)
    {
        $rawParam = str_replace("\t", " ", trim($rawParam));
        $rawParamChunks = explode(" ", $rawParam);
        $raw = substr($rawParam, strlen($rawParamChunks[0]) + 1);

        switch ($rawParamChunks[0]) {
            case '@param':
                return new Param($raw, $signature);
                break;

            case '@throw':
            case '@throws':
                return new Throws($raw);
                break;

            case '@return':
            case '@returns':
                return new Returns($raw);
                break;
            
            default:
                // throw new \Exception("Error Processing " . $rawParam);
                break;
        }
    }
}