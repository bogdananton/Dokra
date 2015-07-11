<?php
namespace Dokra\storage;

use Dokra\Application;
use Dokra\base\Config;

class FileStorage
{
    use Config;

    protected $cacheFolder;

    public function getPath($file)
    {
        if (is_null($this->cacheFolder)) {
            $this->cacheFolder = $this->getConfig(Application::CACHE_TEMPORARY);
        }

        return $this->cacheFolder . '/' . $file;
    }

    public static function getExtension($filePath)
    {
        return strtolower(substr($filePath, strrpos($filePath, '.') + 1));
    }

    public function getFiles($path, array $extensions = ['php', 'wsdl'])
    {
        $file = 'cache.files.json';
        $filePath = $this->getPath($file);

        if (file_exists($filePath)) {
            return json_decode(file_get_contents($filePath));
        }

        $pattern = '/^.+\.(' . implode('|', $extensions) . ')$/i';

        $Directory = new \RecursiveDirectoryIterator($path);
        $Iterator = new \RecursiveIteratorIterator($Directory);
        $Regex = new \RegexIterator($Iterator, $pattern, \RecursiveRegexIterator::GET_MATCH);

        $response = array_keys(iterator_to_array($Regex));

        $this->store($file, $response);
        return $response;
    }

    public function get($filename)
    {
        if (file_exists($filename)) {
            $filePath = $filename;
        } else {
            $filePath = $this->getPath($filename);
        }

        if (file_exists($filePath)) {
            $contents = file_get_contents($filePath);
            $decoded = json_decode($contents);
            if ($decoded) {
                return $decoded;
            } else {
                return $contents;
            }
        }
    }

    public function store($filename, $data)
    {
        switch (true) {
            case is_array($data):
            case is_object($data):
                $contents = json_encode($data, JSON_PRETTY_PRINT);
                break;
            default:
                $contents = $data;
                break;
        }

        file_put_contents($this->getPath($filename), $contents);
    }
}
