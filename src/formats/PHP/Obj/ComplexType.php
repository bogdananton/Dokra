<?php
namespace Dokra\formats\PHP\Obj;


use Dokra\Application;
use Dokra\base\Config;
use Dokra\formats\PHP\Importer;

class ComplexType
{
    use Config;

    public $rawParam;
    public $mainFileName;
    public $mainFileNamespace;
    public $namespace;
    public $filePath;
    public $classDetails;

    public function __construct($param, $mainFileName, $mainFileNamespace)
    {
        $this->rawParam = $param;
        $this->mainFileName = realpath($mainFileName);
        $this->mainFileNamespace = $mainFileNamespace;
        $this->attributes = [];

        $files = $this->getConfig(Application::PROJECT_FILES);

        foreach ($files as $filePath) {
            $filePath = realpath($filePath);

            $contents = $this->getStorage()->get($filePath);

            $classDetails = Importer::getClassDetails($contents);
            $underSameNSString = Importer::normalizeNamespace($this->mainFileNamespace . '\\' . $this->rawParam->type);

            if (null !== $classDetails->namespace || null !== $classDetails->className) {
                $entryNSString = Importer::normalizeNamespace($classDetails->namespace . '\\' . $classDetails->className);

                if ($underSameNSString === $entryNSString) {
                    $this->classDetails = $classDetails;
                    $this->filePath = $filePath;

                    $this->attributes = Importer::getClassAttributes($filePath, $contents, $classDetails);
                }
            }
        }

        unset($this->rawParam);
    }
}