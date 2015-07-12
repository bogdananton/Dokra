<?php
namespace Dokra\storage;
use Dokra\base\Registry;

function file_get_contents($path)
{
    try {
        $mock = Registry::getInstance()->get('mockSystem');
        return $mock->file_get_contents($path);

    } catch (\Exception $e) {
        return \file_get_contents($path);
    }
}

function file_exists($path)
{
    try {
        $mock = Registry::getInstance()->get('mockSystem');
        return $mock->file_exists($path);

    } catch (\Exception $e) {
        return \file_exists($path);
    }
}

namespace tests\storage\FileStorage;
use Dokra\base\Registry;
use Dokra\storage\FileStorage;

class getTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FileStorage|\Mockery::Mock
     */
    public $storage;

    public $filename;
    public $filePath;
    public $filenameExists = true;
    public $filePathExists = true;
    public $contents;

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

    /**
     * When filename exists then use as filepath and if content is not json encoded then return raw contents.
     */
    public function testWhenFilenameExistsThenUseAsFilepathAndIfContentIsNotJsonEncodedThenReturnRawContents()
    {
        $this->storage->shouldNotReceive('path');
        $this->filenameExists = true; // meaning that the filename will be used as the filePath.
        $this->filePath = $this->filename;
        $this->contents = 'A125';

        $mockSystem = Registry::getInstance()->get('mockSystem');
        $mockSystem->shouldReceive('file_exists')->with($this->filename)->andReturn($this->filenameExists);
        $mockSystem->shouldReceive('file_get_contents')->with($this->filePath)->andReturn($this->contents);

        $response = $this->storage->get($this->filename);
        static::assertEquals($this->contents, $response);
    }

    /**
     * When filename doesn't exist then get the file path and if content is json encoded then return decoded contents.
     */
    public function testWhenFilenameDoesnTExistThenGetTheFilePathAndIfContentIsJsonEncodedThenReturnDecodedContents()
    {
        $this->contents = '{"id": 1, "name": "store"}';

        $mockSystem = Registry::getInstance()->get('mockSystem');
        $mockSystem->shouldReceive('file_exists')->with($this->filename)->andReturn(false);
        $mockSystem->shouldReceive('file_exists')->with($this->filePath)->andReturn(true);
        $mockSystem->shouldReceive('file_get_contents')->with($this->filePath)->andReturn($this->contents);

        $this->storage->shouldReceive('path')->with($this->filename)->andReturn($this->filePath);

        $response = $this->storage->get($this->filename);
        static::assertEquals(json_decode($this->contents), $response);
    }

    /**
     * When both filename and filepath don\'t exist then return null.
     */
    public function testWhenBothFilenameAndFilepathDonTExistThenReturnNull()
    {
        $mockSystem = Registry::getInstance()->get('mockSystem');
        $mockSystem->shouldReceive('file_exists')->with($this->filename)->andReturn(false);
        $mockSystem->shouldReceive('file_exists')->with($this->filePath)->andReturn(false);
        $mockSystem->shouldReceive('file_get_contents')->with($this->filePath)->andReturn($this->contents);

        $this->storage->shouldReceive('path')->with($this->filename)->andReturn($this->filePath);

        $response = $this->storage->get($this->filename);
        static::assertNull($response);
    }
}
