<?php
namespace Dokra\storage;

use Dokra\Application;
use Dokra\base\Config;

class FileStorage
{
    use Config;

    protected $cacheFolder;

    public function path($filename)
    {
        if (null === $this->cacheFolder) {
            $this->cacheFolder = $this->getConfig(Application::CACHE_TEMPORARY);
        }
        return implode(DIRECTORY_SEPARATOR, [$this->cacheFolder, $filename]);
    }

    public static function extension($filePath)
    {
        if (is_string($filePath)) {
            $response = pathinfo($filePath, PATHINFO_EXTENSION);
            return $response ? strtolower($response) : null;
        }
    }

    public function files($path, array $extensions = ['php', 'wsdl'])
    {
        $key = implode('.', [
            Application::CACHE_FILES_JSON,
            implode('-', $extensions),
            md5($path),
            'json'
        ]);

        $existingFiles = $this->get($key);
        if ($existingFiles && count($existingFiles) > 0) {
            return $existingFiles;
        }

        $Regex = new \RegexIterator(
            new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path)),
            '/^.+\.(' . implode('|', $extensions) . ')$/i',
            \RecursiveRegexIterator::GET_MATCH
        );

        $files = array_keys(iterator_to_array($Regex));
        sort($files);

        return $this->set($key, $files);
    }

    public function get($filename)
    {
        $filePath = file_exists($filename) ? $filename : $this->path($filename);

        if (file_exists($filePath)) {
            $contents = file_get_contents($filePath);
            $decoded = json_decode($contents);
            return ($decoded || is_array($decoded)) ? $decoded : $contents;
        }
    }

    public function set($filename, $data)
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

        $filePath = $this->path($filename);
        file_put_contents($filePath, $contents);

        return $data;
    }
}
