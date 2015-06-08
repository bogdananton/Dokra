<?php
namespace Dokra\import\from;

use Dokra\assets\InterfaceFileEntry;

interface FromInterface {
    function convertFile(InterfaceFileEntry $interfaceFileEntry);
}