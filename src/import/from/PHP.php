<?php
namespace Dokra\import\from;

use Dokra\assets\InterfaceFileEntry;

class PHP implements FromInterface
{
    const ID = 'php';

    public function convertFile(InterfaceFileEntry $interfaceFileEntry)
    {
        return false;
    }
}