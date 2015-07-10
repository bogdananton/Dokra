<?php
namespace DokraApplication\api;

class BaseAPI
{
    protected $hash;

    public function setHash($hash)
    {
        $this->hash = $hash;
    }
}
