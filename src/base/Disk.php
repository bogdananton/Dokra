<?php
namespace Dokra\base;

class Disk
{
    use RegistryT;

    public static function getExtension($filePath)
    {
        return strtolower(substr($filePath, strrpos($filePath, '.') + 1));
    }

    public function getFiles($path, $extensions = ['php', 'wsdl'])
    {
        $cacheFile = $this->config()->get('cache.temporary') . '/cache.files.json';
        if (file_exists($cacheFile)) {
            return json_decode(file_get_contents($cacheFile));
        }

        $pattern = '/^.+\.(' . implode('|', $extensions) . ')$/i';

        $Directory = new \RecursiveDirectoryIterator($path);
        $Iterator = new \RecursiveIteratorIterator($Directory);
        $Regex = new \RegexIterator($Iterator, $pattern, \RecursiveRegexIterator::GET_MATCH);

        $response = array_keys(iterator_to_array($Regex));

        file_put_contents($cacheFile, json_encode($response, JSON_PRETTY_PRINT));
        return $response;
    }
}
