<?php
namespace Dokra\storage;
use Dokra\base\Registry;

function file_put_contents($path, $content)
{
    try {
        $mock = Registry::getInstance()->get('mockSystem');
        return $mock->file_put_contents($path, $content);

    } catch (\Exception $e) {
        return \file_put_contents($path, $content);
    }
}

namespace tests\storage\FileStorage;
use Dokra\base\Registry;
use Dokra\storage\FileStorage;

class setTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FileStorage
     */
    public $storage;

    public $filename;
    public $filePath;
    public $convertedData;

    public function setUp()
    {
        $this->storage = \Mockery::mock(FileStorage::class)->makePartial();
        Registry::getInstance()->set('mockSystem', \Mockery::mock('mockEnvironment'));

        $this->filename = 'cache.json';
        $this->filePath = '/project/tmp';
    }

    public function tearDown()
    {
        Registry::getInstance()->delete('mockSystem');
        \Mockery::close();
    }

    public function registerMocks()
    {
        $this->storage->shouldReceive('path')->once()->with($this->filename)->andReturn($this->filePath);

        $mockSystem = Registry::getInstance()->get('mockSystem');
        $mockSystem->shouldReceive('file_put_contents')->with($this->filePath, $this->convertedData);
    }

    /**
     * When data input is scalar then store as it is.
     * @var string $input
     * @dataProvider providerScalarValues
     */
    public function testWhenDataInputIsScalarThenStoreAsItIs($input)
    {
        $this->convertedData = $input;
        $this->registerMocks();

        $response = $this->storage->set($this->filename, $input);
        static::assertSame($input, $response);
    }

    public function providerScalarValues()
    {
        return [
            [true],
            [''],
            ['12345'],
            [555555],
            ['/project/Dokra/demo_php']
        ];
    }

    /**
     * When data input is object or array then encode and store.
     * @var \stdClass|array $input
     * @dataProvider providerObjectOrArray
     */
    public function testWhenDataInputIsObjectOrArrayThenEncodeAndStore($input)
    {
        $this->convertedData = json_encode($input, JSON_PRETTY_PRINT);
        $this->registerMocks();

        $response = $this->storage->set($this->filename, $input);
        static::assertSame($input, $response);
    }

    public function providerObjectOrArray()
    {
        $object = new \stdClass();
        $object->id = 2;
        $object->name = 'elements';

        $array = (array)$object;

        return [
            [$object],
            [$array]
        ];
    }
}
