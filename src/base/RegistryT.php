<?php
namespace Dokra\base;

use \Dokra\base\Registry;

trait RegistryT
{
    public function config()
    {
        return Registry::getInstance();
    }
}
