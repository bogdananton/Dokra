<?php
namespace Dokra\import\from;

use Dokra\assets\InterfaceFileEntry;

class WSDL implements FromInterface
{
    const ID = 'wsdl';
    protected $xml;
    protected $issues = [];

    public function convertFile(InterfaceFileEntry $interfaceFileEntry)
    {
        if ($this->load($interfaceFileEntry->filePath)) {
            $response = new \stdClass;
            $response->source = $interfaceFileEntry;
            $response->complexTypes = $this->getComplexTypes();
            $response->methodMessages = $this->getMethodMessages();
            $response->methodOperations = $this->getMethodOperations();

            // @todo do method bindings and validation, for WSDL checking.
            // $response->methodBindings = $this->getMethodBindings();

            return $response;
        }
    }

    protected function performDiagnosticsComplexTypes($complexTypes)
    {
        $usedComplexExtends = [];
        $usedComplexArrayTypes = [];

        // if (empty($complexTypes['complexTypes']) && empty($complexTypes['arrayTypes'])) {
        //     throw new \Exception("Couldn't find complex or array objects definitions. Ignore and catch in methods.");
        // }

        foreach ($complexTypes['complexTypes'] as $label => $object) {
            if ($object->type == 'object' && !empty($object->extends) && isset($object->extends->isCustom)) {
                if ($object->extends->isCustom) {
                    $usedComplexExtends[] = $object->extends->type;
                }
            }
        }

        foreach ($complexTypes['arrayTypes'] as $label => $object) {
            if (!$object->isBasic) {
                $usedComplexArrayTypes[] = $object->ofType;
            }
        }

        $definedComplexTypesLabels = array_keys($complexTypes['complexTypes']);

        $unidentifiedExtendedComplexTypes = array_diff($usedComplexExtends, $definedComplexTypesLabels);
        $unidentifiedArrayComplexTypes = array_diff($usedComplexArrayTypes, $definedComplexTypesLabels);


        if (!empty($unidentifiedArrayComplexTypes)) {
            throw new \Exception("Couldn't find complex objects used by complex array types: " . implode(', ', $unidentifiedArrayComplexTypes) . ".");
        }

        if (!empty($unidentifiedExtendedComplexTypes)) {
            throw new \Exception("Couldn't find complex objects extended by complex types: " . implode(', ', $unidentifiedExtendedComplexTypes) . ".");
        }
    }

    public function getMethodOperations()
    {
        $response = [];

        foreach ($this->xml->portType->operation as $operationNode) {
            $operation = new \stdClass;

            $inputTypeString = $this->getAttribute($operationNode->input, 'message');
            $outputTypeString = (string)$operationNode->output->attributes()->message;
            
            $inputTypeObject = new \stdClass;
            $inputTypeObject->type = $this->getTypeString($inputTypeString);
            $inputTypeObject->isBasic = $this->isBasicType($inputTypeString);

            $outputTypeObject = new \stdClass;
            $outputTypeObject->type = $this->getTypeString($outputTypeString);
            $outputTypeObject->isBasic = $this->isBasicType($outputTypeString);
           
            $operation->inputMessage = $inputTypeObject;
            $operation->outputMessage = $outputTypeObject;

            $operation->methodName = trim((string)$operationNode->attributes()->name);
            $operation->methodDocumentation = trim((string)$operationNode->documentation);

            $response[] = $operation;
        }

        return $response;
    }

    public function getMethodMessages()
    {
        $response = [];

        $methods = $this->xml->message;
        foreach ($methods as $j => $message) {
            $name = trim($message->attributes()->name);

            $object = new \stdClass;
            $object->name = $name;
            $object->parts = [];

            foreach ($message->part as $part) {
                $partName = trim($part->attributes()->name);
                $partType = trim($part->attributes()->type);

                $partObject = new \stdClass;
                $partObject->type = $this->getTypeString($partType);
                $partObject->isBasic = $this->isBasicType($partType);
                $partObject->isNull = $this->isNullMessagePart($part);

                $object->parts[] = $partObject;
            }
            $response[] = $object;
        }
        return $response;
    }

    protected function isNullMessagePart($part)
    {
        foreach ($this->getNamespaceCodes() as $namespace) {
            if ($part->attributes($namespace)->null) {
                return (bool)$part->attributes($namespace)->null;
            }
        }
        return false;
    }

    protected function getComplexTypes()
    {
        $response = [
            'complexTypes' => [],
            'arrayTypes' => []
        ];

        // if (isset($this->xml->types) && empty($this->xml->types)) {
            foreach ($this->getNamespaceCodes() as $namespace) {
                $types = $this->xml->types->xpath($namespace . ':schema/' . $namespace . ':complexType');
                if (!empty($types)) {
                    foreach ($types as $index => $type) {
                        $complexTypeName = $this->getElementName($type);
                        $objectType = $this->extractType($type);

                        if ($objectType) {
                            switch ($objectType->type) {
                                case 'array':
                                    $response['arrayTypes'][$complexTypeName] = $objectType;
                                break;
                                case 'object':
                                    $response['complexTypes'][$complexTypeName] = $objectType;
                                break;
                                default:
                                    throw new \Exception(json_encode($objectType));
                                break;
                            }
                        }
                    }
                }
            }
        // }

        // throw exception if something is missing
        $this->performDiagnosticsComplexTypes($response);

        return $response;
    }

    protected function extractType(\SimpleXMLElement $typeNode)
    {
        foreach ($this->getNamespaceCodes() as $namespace) {
            $sequences = $this->getElementSequencesFromNode($typeNode, $namespace);

            $complexContents = $typeNode->xpath($namespace . ':complexContent/' . $namespace . ':restriction/' . $namespace . ':attribute');
            if ($complexContents) {
                if ($complexContents[0]->attributes()->ref == 'soapenc:arrayType') {
                    preg_match('/wsdl\:arrayType\=\"([\:\w\[\]]+)\"/', $complexContents[0]->asXML(), $matches);
                    if ($matches) {
                        $rawTypeString = $matches[1];

                        $object = (object)[
                            'type' => 'array',
                            'ofType' => $this->getTypeString($rawTypeString),
                            'isBasic' => $this->isBasicType($rawTypeString)
                        ];

                        return $object;
                    }
                }
            }

            $extendsObject = null;

            $complexContentsExtends = $typeNode->xpath($namespace . ':complexContent/' . $namespace . ':extension');
            if ($complexContentsExtends) {
                $extendsObjectNameString = $this->getElementBaseName($complexContentsExtends[0]);
                $sequences = $this->getElementSequencesFromNode($complexContentsExtends[0], $namespace);

                $extendsObject = (object)[
                    'type' => $this->getTypeString($extendsObjectNameString),
                    'isCustom' => !$this->isBasicType($extendsObjectNameString)
                ];

            }

            if (!empty($sequences)) {
                return (object)[
                    'type' => 'object',
                    'elements' => $this->extractFromSequences($sequences),
                    'extends' => $extendsObject
                ];
            }
        }
        // unknown or not found for known namespaces. debug:
        // return $typeNode->asXML();
    }

    protected function extractFromSequences($sequences)
    {
        $response = [];
        foreach ($this->getNamespaceCodes() as $namespace) {
            foreach ($sequences as $j => $sequence) {
                $elements = $sequence->xpath($namespace . ':element');
                if (!empty($elements)) {
                    foreach ($elements as $element) {
                        $elementName = $this->getElementName($element);
                        $elementRawType = $this->getElementRawType($element);
                        $isNillable = $this->getElementNillable($element);

                        $response[$elementName] = (object)[
                            // 'name' => $elementName,
                            'label' => $elementRawType->label,
                            'isCustom' => $elementRawType->isCustom,
                            'isNillable' => $isNillable
                        ];
                    }
                }
            }
        }
        return $response;
    }

    protected function getElementNillable($element)
    {
        return (bool)trim((string)$element->attributes()->nillable);
    }

    public function getElementRawType($element)
    {
        $elementType = trim((string)$element->attributes()->type);

        $response = new \stdClass;
        $response->isCustom = !$this->isBasicType($elementType);
        $response->label = $this->getTypeString($elementType);

        return $response;
    }

    protected function getNamespaceCodes()
    {
        $namespaces = $this->xml->getNamespaces(true);
        return array_filter(array_keys($namespaces));
    }

    protected function load($filePath)
    {
        $this->xml = @simplexml_load_file($filePath);
        return ($this->xml);
    }

    protected function getElementName($type)
    {
        return trim((string)$type->attributes()->name);
    }

    protected function getElementSequencesFromNode($complexContentsExtendsItem, $namespace)
    {
        $sequences = $complexContentsExtendsItem->xpath($namespace . ':sequence');
        if (empty($sequences)) {
            $sequences = $complexContentsExtendsItem->xpath($namespace . ':all');
        }
        return $sequences;
    }

    protected function isBasicType($rawTypeString)
    {
        $rawType = explode(':', $rawTypeString);
        return in_array($rawType[0], $this->getNamespaceCodes());
    }

    protected function getTypeString($rawTypeString)
    {
        $rawType = explode(':', $rawTypeString);
        $typeObject = $rawType[1];

        if (substr($typeObject, -2) == '[]') {
            $typeObject = substr($typeObject, 0, -2);
        }

        return $typeObject;
    }

    protected function getElementBaseName($complexContentsExtendsItem)
    {
        return $this->getAttribute($complexContentsExtendsItem, 'base');
    }

    protected function getAttribute($element, $name)
    {
        return trim((string)$element->attributes()->{$name});
    }
}
