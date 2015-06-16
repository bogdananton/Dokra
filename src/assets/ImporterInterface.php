<?php
namespace Dokra\assets;

use Dokra\assets\APIFileEntry;

interface ImporterInterface {
    function convertFile(APIFileEntry $interfaceFileEntry);
}