<?php
require_once(realpath(__DIR__ . '/../vendor/autoload.php'));

class PHPUnitProtectedHelper
{
    protected $target;
    protected $refObject;

    public function __construct($target)
    {
        $this->target = $target;
        $this->refObject = new \ReflectionObject($this->target);
    }

    public function makeCall($method)
    {
        $class = new \ReflectionClass(get_class($this->target));
        $method = $class->getMethod($method);
        $method->setAccessible(true);
        return $method->invokeArgs($this->target, array());
    }

    public function getValue($attribute)
    {
        return \PHPUnit_Framework_Assert::readAttribute($this->target, $attribute);
    }

    public function setValue($attribute, $value)
    {
        $refProperty = $this->refObject->getProperty($attribute);
        $refProperty->setAccessible(true);
        $refProperty->setValue($this->target, $value);
    }
}
