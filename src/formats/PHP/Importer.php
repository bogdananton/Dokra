<?php
namespace Dokra\formats\PHP;

use Dokra\Application;
use Dokra\assets\APIFileEntry;
use Dokra\base\Config;
use Dokra\base\Importer as ImporterA;
use Dokra\formats\PHP\Obj\ClassEntry;

class Importer extends ImporterA
{
    use Config;

    protected static $errors = [];

    public function getId()
    {
        return 'php';
    }

    public static function getErrors()
    {
        return self::$errors;
    }

    protected static function getClassNameFromLine($line)
    {
        preg_match('/^class\s([\w_\d]+)/', $line, $matches);
        if ($matches) {
            return $matches[1];
        }
    }

    protected static function getClassAliasFromLine($line)
    {
        preg_match('/class\s([\w_\d]+)\sextends\s([\\\|\w_\d]+)/', $line, $matches);
        if ($matches) {
            return $matches[2];
        }
    }

    public function convertFile(APIFileEntry $interfaceFileEntry)
    {
        $response = new \stdClass();
        $response->source = $interfaceFileEntry;
        $response->entry = new ClassEntry($interfaceFileEntry->filePath);

        return $response;
    }

    public static function normalizeNamespace($inputClassName)
    {
        $inputClassName = explode('\\', $inputClassName);
        $inputClassName = array_filter($inputClassName);
        return implode('\\', $inputClassName);
    }

    public static function getClassAttributes($filePath, $contents, $classDetails)
    {
        $response = [];

        $className = self::normalizeNamespace($classDetails->namespace . '\\' . $classDetails->className);
        $importer = new static;

        try {
            $flagReflection = $importer->getConfig(Application::FLAG_USE_REFLECTION);

            if ($flagReflection && file_exists($filePath)) {
                require_once $filePath;

                if (class_exists($className)) {
                    $rClass = new \ReflectionClass($className);
                    $properties = $rClass->getProperties();
                    $defaultProperties = $rClass->getDefaultProperties();

                    foreach ($properties as $property) {
                        $pClass = new \ReflectionProperty($className, $property->name);

                        $propertyItem = new \stdClass();
                        $propertyItem->name = $pClass->getName();
                        $propertyItem->docblock = self::filterDocBlock($pClass->getDocComment());
                        $propertyItem->default = $defaultProperties[$propertyItem->name];
                        $propertyItem->type = null;

                        $docBlockParts = explode(PHP_EOL, $propertyItem->docblock);
                        foreach ($docBlockParts as $line) {
                            $line = trim($line);
                            preg_match('/^\@(var|type)\s([\\\|\w\d_]+)/', $line, $matches);
                            if ($matches) {
                                $propertyItem->type = $matches[2];
                            }
                        }

                        if (empty($propertyItem->type)) {
                            self::$errors[] = 'Attribute ' . $propertyItem->name . ' (from ' . $className . ') doesn\'t have the type set up in the doc block. File: [' . $filePath . ']';
                        }

                        $response[] = $propertyItem;
                    }
                }
            } else {
                // @todo Handle case when file scraping is required for extracting the params.
            }

        } catch (\Exception $e) {
            // failed or flag not enabled. Just fallback to file contents scraping.
        }

        return $response;
    }

    public static function filterDocBlock($rawDocBlock)
    {
        $response = [];

        $lines = explode(PHP_EOL, $rawDocBlock);
        foreach ($lines as $line) {
            $line = trim($line);
            if (!in_array($line, ['//', '/*', '/**', '*/', '*'], false)) {
                while (substr($line, 0, 1) === "*") {
                    $line = substr($line, 1);
                }

                $response[] = $line;
            }
        }

        return implode(PHP_EOL, $response);
    }

    public static function getClassDetails($contents)
    {
        $response = new \stdClass();
        $response->namespace = self::getNamespace($contents);
        $response->className = null;
        $response->extendsClass = null;
        $response->extendsAlias = null;
        $response->uses = [];

        $lines = explode("\n", $contents);
        foreach ($lines as $line) {
            $line = trim($line);

            preg_match('/^use\s([\\\|\w_\d]+)/', $line, $matches);
            if ($matches) {
                $className = $matches[1];
                $alias = substr($className, strrpos($className, '\\') + 1);
                $response->uses[$className] = $alias;
            }

            if (is_null($response->className)) {
                if ($result = self::getClassNameFromLine($line)) {
                    $response->className = $result;
                }

                if ($result = self::getClassAliasFromLine($line)) {
                    $response->extendsAlias = $result;
                }
            }
        }

        if (null !== $response->extendsAlias && count($response->uses) > 0) {
            foreach ($response->uses as $key => $value) {
                if ($value == $response->extendsAlias) {
                    $response->extendsClass = $key;

                } else if ($key == $response->extendsAlias) {
                    $response->extendsClass = $key;
                }
            }
        }

        return $response;
    }

    public static function getNamespace($contents)
    {
        $lines = explode("\n", $contents);

        foreach ($lines as $line) {
            $line = trim($line);
            preg_match('/namespace\s(.*)\;/', $line, $matches);
            if ($matches) {
                return $matches[1];
            }
        }
    }
}