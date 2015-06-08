<?php
namespace Dokra\base;

class Disk
{
    public function getFiles($path, $extensions = ['php', 'wsdl'])
    {
        $pattern = '/^.+\.(' . implode('|', $extensions) . ')$/i';

        $Directory = new \RecursiveDirectoryIterator($path);
        $Iterator = new \RecursiveIteratorIterator($Directory);
        $Regex = new \RegexIterator($Iterator, $pattern, \RecursiveRegexIterator::GET_MATCH);

        return array_keys(iterator_to_array($Regex));
    }
}
