<?php
namespace Dokra\base;

use Dokra\Application;
use Dokra\exceptions\SetupException;
use Dokra\storage\FileStorage;

trait Config
{
    public function config()
    {
        return Registry::getInstance();
    }

    public function initConfig(array $configuration)
    {
        $rClass = new \ReflectionClass(Application::class);
        $configurationConstants = $rClass->getConstants();

        foreach ($configurationConstants as $key => $value) {
            if (array_key_exists($value, $configuration)) {
                $this->setConfig($value, $configuration[$value]);
            }
        }
    }

    public function getConfig($key)
    {
        return $this->config()->get($key);
    }

    public function setConfig($key, $value)
    {
        $this->config()->set($key, $value);
    }

    /**
     * @return FileStorage
     */
    public function getStorage()
    {
        return $this->getConfig('storage');
    }

    public function getProjectPath()
    {
        try {
            return $this->getConfig(Application::PROJECT_PATH);
        } catch (\Exception $e) {
            throw new SetupException($e->getMessage());
        }
    }

    public function setProjectFiles($files)
    {
        $this->setConfig(Application::PROJECT_FILES, $files);
    }
}
