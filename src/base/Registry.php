<?php
namespace Dokra\base;

class Registry
{
    private $registry = [];

    private static $instance = null;
 
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Registry();
        }
        return self::$instance;
    }
    
    public function set($key, $value) {
        $this->registry[$key] = $value;
    }

    public function get($key) {
        if (!array_key_exists($key, $this->registry)) {
            throw new \InvalidArgumentException(sprintf('Entry %s not set.', $key));
        }
        return $this->registry[$key];
    }

    public function delete($key)
    {
        if (!array_key_exists($key, $this->registry)) {
            throw new \InvalidArgumentException(sprintf('Entry %s not set.', $key));
        }

        unset($this->registry[$key]);
    }

    public function reset()
    {
        $this->registry = [];
    }
}
