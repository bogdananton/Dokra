<?php
namespace Dokra\base;

trait RegistryT
{
    public function config()
    {
        return Registry::getInstance();
    }
}
