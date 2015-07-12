<?php
namespace tests\storage\FileStorage;


use Dokra\Application;
use Dokra\storage\FileStorage;

class pathTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Dokra\storage\FileStorage
     */
    public $storage;

    public function setUp()
    {
        $this->storage = new FileStorage();
    }

    /**
     * When cache folder not set then prepare and return path.
     */
    public function testWhenCacheFolderNotSetThenPrepareAndReturnPath()
    {
        $inputFilename = 'cache-123.json';
        $configurationCacheFolder = '/project/Dokra/tmp';

        $this->storage->setConfig(Application::CACHE_TEMPORARY, $configurationCacheFolder);
        $response = $this->storage->path($inputFilename);

        $expected = $configurationCacheFolder . DIRECTORY_SEPARATOR . $inputFilename;
        static::assertEquals($expected, $response);

        $helper = new \PHPUnitProtectedHelper($this->storage);
        static::assertEquals($configurationCacheFolder, $helper->getValue('cacheFolder'));
    }


    /**
     * When cache folder is set then return path without reloading the folder settings.
     */
    public function testWhenCacheFolderIsSetThenReturnPathWithoutReloadingTheFolderSettings()
    {
        $helper = new \PHPUnitProtectedHelper($this->storage);
        $inputFilename = 'cache-123.json';

        $cacheFolderExistingSettings = '/project/Dokra/cache';
        $helper->setValue('cacheFolder', $cacheFolderExistingSettings);

        $configurationCacheFolder = '/project/Dokra/tmp';
        $this->storage->setConfig(Application::CACHE_TEMPORARY, $configurationCacheFolder);

        $response = $this->storage->path($inputFilename);
        $expected = $cacheFolderExistingSettings . DIRECTORY_SEPARATOR . $inputFilename;

        static::assertEquals($expected, $response);
        static::assertEquals($cacheFolderExistingSettings, $helper->getValue('cacheFolder'));
    }
}
