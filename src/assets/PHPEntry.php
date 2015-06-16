<?php
namespace Dokra\assets;

use \Dokra\base\RegistryT;

class PHPEntry
{
    use RegistryT;

    public $filename;
    public $methods;
    // private $contents;

    public $namespace;

    public $extendsAlias;
    public $extendsClass;
    public $extendsFile;

    public $extendsPHP;

    public static function processRawDockblock($raw)
    {
        return new PHPMethodDocblock($raw);
    }

    public function getMethodDocblock($methodName)
    {
        $raw = $this->getMethodDocRawblock($methodName);
        if ($raw) {
            return self::processRawDockblock($raw);
        }

        return false;
    }

    public function getMethodDocRawblock($methodName)
    {
        if (!empty($this->methods)) {
            foreach ($this->methods as $method) {
                if ($method['method'] == $methodName) {
                    return $method['rawDocblock'];
                }
            }
        }

        if (!empty($this->extendsPHP)) {
            return $this->extendsPHP->getMethodDocRawblock($methodName);
        }

        return false;
    }

    public function __construct($filename)
    {
        $this->filename = $filename;

        if (!is_null($filename)) {
            $contents = file_get_contents($filename);

            preg_match('/namespace\s(.*)\;/', $contents, $matches);
            if ($matches) {
                $this->namespace = $matches[1];
            }

            preg_match('/class\s([\w_]+)\sextends\s([\w_]+)/', $contents, $matches);
            if ($matches) {
                // print_r($matches);
                $this->extendsAlias = $matches[2];

                preg_match('/use\s(.*)\sas\s' . preg_quote($matches[2]) . '\;/', $contents, $matchesAlias);
                if ($matchesAlias) {
                    $this->extendsClass = $matchesAlias[1];
                } else {
                    preg_match('/use\s(.*)' . preg_quote($matches[2]) . '\;/', $contents, $matchesAlias);
                    if ($matchesAlias) {
                        $this->extendsClass = $matchesAlias[1] . '\\' . $matches[2];

                        $this->extendsClass = explode('\\', $this->extendsClass);
                        $this->extendsClass = array_filter($this->extendsClass);
                        $this->extendsClass = implode('\\', $this->extendsClass);
                    }
                }
            }

            preg_match_all('/public\sfunction\s([\w]+)\s?\(/', $contents, $matches);
            if ($matches) {
                $this->methods = $matches[1];
            }

            if ((!empty($this->extendsClass)) && (strpos($this->extendsClass, '\\') === false) && !empty($this->namespace)) {
                $this->extendsClass = $this->namespace . '\\' . $this->extendsClass;
            }

            if (empty($this->extendsClass) && !empty($this->extendsAlias)) {
                if (!empty($this->namespace)) {
                    $this->extendsClass = $this->namespace . '\\' . $this->extendsAlias;
                } else {
                    $this->extendsClass = $this->extendsAlias;
                }
            }

            $seekForNamespace = substr($this->extendsClass, 0, strrpos($this->extendsClass, '\\'));
            $seekForClass = explode('\\', $this->extendsClass);
            $seekForClass = end($seekForClass);

            if (substr($seekForNamespace, 0, 1) == '\\') {
                $seekForNamespace = substr($seekForNamespace, 1);
            }

            foreach ($this->config()->get("php.files") as $file) {
                if (strtolower(substr($file, -4)) != '.php') {
                    continue;
                }

                $contentsLoop = file_get_contents($file);

                preg_match('/namespace\s' .  preg_quote($seekForNamespace) . '\;/', $contentsLoop, $matches);
                if ($matches) {
                    preg_match('/class\s' .  preg_quote($seekForClass) . '/', $contentsLoop, $matches);
                    if ($matches) {
                        $this->extendsPHP = new self($file);
                    }
                }
            }

            $methodList = $this->methods;
            foreach ($methodList as $key => $value) {
                $methodList[$key] = preg_quote($value);
            }

            $lines = explode("\n", $contents);
            foreach ($lines as $i => $line) {
                preg_match('^public\sfunction\s(' .  implode('|', $methodList) . ')\s?\(^', $line, $matches);
                if ($matches) {

                    $rawDocblock = array();
                    $listen = false;
                    $append = true;

                    for ($j=0; $j <= 20; $j++) {
                        $lineCursor = $i - $j;
                        if ($lineCursor > 0) {

                            if (trim($lines[$lineCursor]) == '*/') {
                                $listen = true;
                            }

                            if ($append && ($j < 2 || $listen)) {
                                $insertedLine = trim($lines[$lineCursor]);
                                if (!empty($insertedLine)) {
                                    array_unshift($rawDocblock, $insertedLine);
                                }
                            }

                            if ((trim($lines[$lineCursor]) == '/**') || (trim($lines[$lineCursor]) == '/*')) {
                                $listen = false;
                                $append = false;
                            }
                        }
                    }

                    $this->methods[ array_search($matches[1], $this->methods) ] = array(
                        'line' => $i,
                        'method' => $matches[1],
                        'rawDocblock' => $rawDocblock,
                        'processed' => $this->processRawDockblock($rawDocblock)
                    );
                }
            }

        }
    }
}