<?php
namespace tests\storage\FileStorage;


use Dokra\Application;
use Dokra\storage\FileStorage;

class filesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FileStorage|\Mockery::Mock
     */
    public $storage;

    public function setUp()
    {
        $this->storage = \Mockery::mock(FileStorage::class)->makePartial();
    }

    public function tearDown()
    {
        \Mockery::close();
    }

    /**
     * When files are already set then return them from the disk.
     */
    public function testWhenFilesAreAlreadySetThenReturnThemFromTheDisk()
    {
        $files = [
            '/project/file1.php',
            '/project/file2.php',
            '/project/file3.php',
            '/project/file4.wsdl'
        ];

        $path = '/project/application';

        $key = Application::CACHE_FILES_JSON . '.php-wsdl.' . md5($path) . '.json';

        $this->storage
            ->shouldReceive('get')
            ->with($key)
            ->once()
            ->andReturn($files);

        static::assertEquals($files, $this->storage->files($path));
    }


    /**
     * When scanning files extract only requested extensions.
     */
    public function testWhenScanningFilesExtractOnlyRequestedExtensions()
    {
        $appPath = realpath(__DIR__ . '/../../../sample/application');
        $cachePath = realpath(__DIR__ . '/../../../cache');

        $this->storage->setConfig(Application::CACHE_TEMPORARY, $cachePath);

        $expected = [
            $appPath . '/app/views/soap-rpc/element/element-2.0.wsdl',
            $appPath . '/app/views/soap-rpc/element/element-1.0.wsdl',
            $appPath . '/app/views/soap-rpc/user/user-1.0.wsdl'
        ];

        $key = Application::CACHE_FILES_JSON . '.wsdl.' . md5($appPath) . '.json';
        $cacheFilePath = $cachePath . '/' . $key;

        // clear cache file
        if (file_exists($cacheFilePath)) {
            unlink($cacheFilePath);
        }
        static::assertFalse(file_exists($cacheFilePath));

        // execute
        $response = $this->storage->files($appPath, ['wsdl']);

//        var_dump($cacheFilePath); die();

        usleep(100);

        static::assertEquals($expected, $response);
        static::assertTrue(file_exists($cacheFilePath));
        static::assertEquals($expected, json_decode(\file_get_contents($cacheFilePath)));
    }
}
