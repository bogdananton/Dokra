<?php
namespace tests\Dokra;

class ConstructTest extends \PHPUnit_Framework_TestCase
{
    /**
     * class exists
     */
    public function testClassExists()
    {
        $this->assertTrue(class_exists('\Dokra\Dokra'));
    }
}
