<?php
namespace Dokra\diff;

class WSDLVersionChanges
{
    protected $entries;
    protected $hashMaps = [];

    public function from($wsdlInterfaces)
    {
        $this->entries = $wsdlInterfaces;
        return $this;
    }

    public function run()
    {
        $this->makeInterfacesHashMapsIndexes();
        $this->extractAllChanges();

        return $this->hashMaps;
    }

    protected function extractAllChanges()
    {
        foreach ($this->hashMaps as $ep => $endpointDetails) {
            $previousVersionHashMapIndex = null;

            foreach ($endpointDetails as $v => $hashMapEntry) {
                if (!is_null($previousVersionHashMapIndex)) {
                    $currentVersionHashMapIndex = $this->hashMaps[$ep][$v]->index;

                    $currentWSDL = $this->entries[$currentVersionHashMapIndex];
                    $previousWSDL = $this->entries[$previousVersionHashMapIndex];

                    // hack below --v
                    $this->hashMaps[$ep][$v] = $this->extractItemChanges($currentWSDL, $previousWSDL);
                } else {
                    unset($this->hashMaps[$ep][$v]);
                    // end hack --^
                }

                $previousVersionHashMapIndex = $hashMapEntry->index;
            }
        }
    }

    protected $methodsFromWSDL = [];

    protected function getMethodsFromWSDL($WSDLEntry)
    {
        $hash = md5(json_encode($WSDLEntry));
        if (isset($this->methodsFromWSDL[$hash])) {
            return $this->methodsFromWSDL[$hash];
        }

        $response = [];

        foreach ($WSDLEntry->methodOperations as $operation) {
            $response[$operation->methodName] = $operation;
        }

        $this->methodsFromWSDL[$hash] = $response;
        return $response;
    }

    protected function extractItemChanges($currentWSDL, $previousWSDL)
    {
        $changes = new \stdClass();

//        $changes->methodsAdded = $this->extractItemChangesMethodsAddedOrRemoved($currentWSDL, $previousWSDL);
//        $changes->methodsRemoved = $this->extractItemChangesMethodsAddedOrRemoved($previousWSDL, $currentWSDL);
        $changes->methodsSignatureChanges = $this->extractItemChangesMethodSignatures($currentWSDL, $previousWSDL);
//        $changes->methodsOutputResponse = null;
//        $changes->methodsInputResponse = null;
//        $changes->objectsAdded = null;
//        $changes->objectsRemoved = null;
//        $changes->objectsChanged = null;
//        $changes->documentationChanged = null;

//        print_r($currentWSDL);
        return $changes;
    }

    protected function extractItemChangesMethodSignatures($currentWSDL, $previousWSDL)
    {
        $response = [];

        $currentMethods = $this->getMethodsFromWSDL($currentWSDL);
        $previousMethods = $this->getMethodsFromWSDL($previousWSDL);

        foreach ($currentMethods as $methodName => $currentMethod) {
            if (isset($previousMethods[$methodName])) {
                $previousMethod = $previousMethods[$methodName];
                $currentTypeInputMessage = $currentMethod->inputMessage;
                $currentTypeOutputMessage = $currentMethod->outputMessage;
                $previousTypeInputMessage = $previousMethod->inputMessage;
                $previousTypeOutputMessage = $previousMethod->outputMessage;

                if (!$currentTypeInputMessage->isBasic && !$previousTypeInputMessage->isBasic) {
                    $currentInputMessage = $currentWSDL->methodMessages[$currentTypeInputMessage->type];
                    $previousInputMessage = $previousWSDL->methodMessages[$previousTypeInputMessage->type];

                    $currentInputLabels = array_keys($currentInputMessage);
                    $previousInputLabels = array_keys($previousInputMessage);

                    $currentInputMessageTypes = $this->getStructureWithTypeLabelFromMessage($currentInputMessage);
                    $previousInputMessageTypes = $this->getStructureWithTypeLabelFromMessage($previousInputMessage);

                    if ($currentInputMessageTypes != $previousInputMessageTypes) {
                        // check if the current types and flags were not changed
                        // is this just a label update?
                        // are there extra / less parameters?

                        $addedParams = array_diff($currentInputLabels, $previousInputLabels);
                        $removedParams = array_diff($previousInputLabels, $currentInputLabels);

                        if (!empty($addedParams) || !empty($removedParams)) {
                            $differentParameterCount = count($currentInputLabels) != count($previousInputLabels);
                            // let's check if the added / removed params are optional

                            $diffBackwardCompatible = true;
                            $this->allParametersAreNillable($addedParams, $diffBackwardCompatible, $currentInputMessage);
                            $this->allParametersAreNillable($removedParams, $diffBackwardCompatible, $previousInputMessage);

                            $diffParameterStructure = [];
                            $parameterHasSameStructure = true;

                            if (!empty($addedParams) && (array_keys($addedParams) == array_keys($removedParams))) {
                                // so the only change is that some parameters were replaced by others
                                // check the type to see if only the label has changed
                                $this->checkSignaturesHaveTheSameStructure($currentWSDL, $previousWSDL, $addedParams, $currentInputMessage, $removedParams, $previousInputMessage, $diffParameterStructure, $parameterHasSameStructure);
                            }

                            if (!$differentParameterCount && !$diffBackwardCompatible && $parameterHasSameStructure) {
                                // when the method has the same method param count and same structure for the renamed params then the method is backward compatible
                                $diffBackwardCompatible = true;
                            }

                            $this->appendSignatureChangeItem($response, $methodName, (object)[
                                "messageType" => 'input',
                                "changeType"  => ($differentParameterCount) ? 'different parameter count' : 'different parameter names',
                                "current"     => $currentInputLabels,
                                "previous"    => $previousInputLabels,
                                "backwardsCompatible" => $diffBackwardCompatible,
                                "added" => $addedParams,
                                "removed" => $removedParams,
                                "diffParameterStructure" => empty($diffParameterStructure) ? false : $diffParameterStructure
                            ]);
                        }

                    } else {
                        // the signature looks the same, but is it?
                        $diffParameterStructure = [];
                        $parameterHasSameStructure = true;

                        $this->checkSignaturesHaveTheSameStructure($currentWSDL, $previousWSDL, $currentInputLabels, $currentInputMessage, $previousInputLabels, $previousInputMessage, $diffParameterStructure, $parameterHasSameStructure);

                        if (!empty($diffParameterStructure)) {
                            $this->appendSignatureChangeItem($response, $methodName, (object)[
                                "messageType" => 'input',
                                "changeType"  => 'parameter structure changed',
                                "parameters"  => $currentInputLabels,
                                "diffParameterStructure" => $diffParameterStructure
                            ]);
                        }
                    }
                }
                $this->checkSignatureChangesWhenBasicTypeMessages($currentTypeInputMessage, $previousTypeInputMessage, $methodName, $currentTypeOutputMessage, $previousTypeOutputMessage, $response);

                // check output
                $diffOutputStructure = [];
                $outputHasSameStructure = true;

                $currentOutputMessage = $currentWSDL->methodMessages[$currentTypeOutputMessage->type];
                $previousOutputMessage = $previousWSDL->methodMessages[$previousTypeOutputMessage->type];
                $currentOutputLabels = array_keys($currentOutputMessage);
                $previousOutputLabels = array_keys($previousOutputMessage);

                $this->checkSignaturesHaveTheSameStructure($currentWSDL, $previousWSDL, $currentOutputLabels, $currentOutputMessage, $previousOutputLabels, $previousOutputMessage, $diffOutputStructure, $outputHasSameStructure);

                if (!empty($diffOutputStructure)) {
                    $this->appendSignatureChangeItem($response, $methodName, (object)[
                        "messageType" => 'output',
                        "changeType"  => 'parameter structure changed',
                        "structure" => $diffOutputStructure
                    ]);
                }
            }
        }

        return $response;
    }

    protected function getParameterStructure($structure, $wsdl)
    {
        if ($structure->isBasic) {
            return $structure->type;
        }

        if (isset($wsdl->complexTypes['complexTypes'][$structure->type])) {
            $structureDefinition = $wsdl->complexTypes['complexTypes'][$structure->type];

            if ($structureDefinition->type != 'object') {
                // @todo catch this case or remove the definition
                echo 'NOT object: ';
                print_r($structureDefinition);
                die();
            }

            $response = new \stdClass();

            if (!empty($structureDefinition->elements)) {
                foreach ($structureDefinition->elements as $parameterName => $parameterDetail) {
                    $this->appendParameterStructure($wsdl, $parameterDetail, $parameterName, $response);
                }
            }

            if (!empty($structureDefinition->extends)) {
                $extending = $this->getParameterStructure($structureDefinition->extends, $wsdl);
                foreach ($extending as $parameterName => $parameterDetail) {
                    if (!isset($response->{$parameterName})) {
                        $this->appendParameterStructure($wsdl, $parameterDetail, $parameterName, $response);
                    }
                }
            }

            return $response;
        }

        if (isset($wsdl->complexTypes['arrayTypes'][$structure->type])) {
            $structureDefinition = $wsdl->complexTypes['arrayTypes'][$structure->type];

            if ($structureDefinition->isBasic) {
                return [$structureDefinition->ofType];
            }

            $subStructure = new \stdClass();
            $subStructure->isBasic = false;
            $subStructure->type = $structureDefinition->ofType;

            return [$this->getParameterStructure($subStructure, $wsdl)];
        }

        throw new \Exception("The [" . $structure->type . "] complex type was not found for the WSDL file [" . $wsdl->source->filePath . "].");
    }

    protected function makeInterfacesHashMapsIndexes()
    {
        foreach ($this->entries as $index => $entry) {
            $version = $entry->source->version;
            $endpoint = $entry->source->endpoint;

            if (!isset($this->hashMaps[$endpoint])) {
                $this->hashMaps[$endpoint] = [];
            }

            $this->hashMaps[$endpoint][$version] = new \stdClass();
            $this->hashMaps[$endpoint][$version]->index = $index;
            $this->hashMaps[$endpoint][$version]->changes = [];
        }

        $this->sortInterfacesHashMapsIndexes();
    }

    protected function sortInterfacesHashMapsIndexes()
    {
        ksort($this->hashMaps);
        foreach ($this->hashMaps as $endpoint => $endpointDetails) {
            ksort($this->hashMaps[$endpoint]);
        }
    }

    /**
     * @param $sourceA currentWSDL if getting for added methods. previousWSDL if getting removed methods.
     * @param $sourceB previousWSDL if getting for added methods. currentWSDL if getting removed methods.
     *
     * @return array|null
     */
    protected function extractItemChangesMethodsAddedOrRemoved($sourceA, $sourceB)
    {
        $sourceAMethods = $this->getMethodsFromWSDL($sourceA);
        $sourceBMethods = $this->getMethodsFromWSDL($sourceB);

        $methodNames = array_diff(array_keys($sourceAMethods), array_keys($sourceBMethods));

        if (!empty($methodNames)) {
            sort($methodNames);
            return $methodNames;
        }
    }

    protected function appendSignatureChangeItem(&$response, $methodName, $changeSignature)
    {
        if (!isset($response[$methodName])) {
            $response[$methodName] = [];
        }

        $response[$methodName][] = $changeSignature;
    }

    protected function checkSignatureChangesWhenBasicTypeMessages($currentTypeInputMessage, $previousTypeInputMessage, $methodName, $currentTypeOutputMessage, $previousTypeOutputMessage, &$response)
    {
        // BEGIN: the scenario below should not happen, but we'll leave here just in case and for debugging
        if ($currentTypeInputMessage->isBasic && $previousTypeInputMessage->isBasic) {
            if ($currentTypeInputMessage->type != $previousTypeInputMessage->type) {
                $changeSignature = new \stdClass;
                $changeSignature->messageType = 'input';
                $changeSignature->changeType = 'different parameter basic type';
                $changeSignature->previousType = $previousTypeInputMessage->type;
                $changeSignature->currentType = $currentTypeInputMessage->type;

                $this->appendSignatureChangeItem($response, $methodName, $changeSignature);
            }
        }

        if ((!$currentTypeInputMessage->isBasic && $previousTypeInputMessage->isBasic) || ($currentTypeInputMessage->isBasic && !$previousTypeInputMessage->isBasic)) {
            $changeSignature = new \stdClass;
            $changeSignature->messageType = 'input';
            $changeSignature->changeType = 'different parameter basic type';
            $changeSignature->previousType = $previousTypeInputMessage->type;
            $changeSignature->currentType = $currentTypeInputMessage->type;

            $this->appendSignatureChangeItem($response, $methodName, $changeSignature);
        }

        if ($currentTypeOutputMessage->isBasic && $previousTypeOutputMessage->isBasic) {
            if ($currentTypeOutputMessage->type != $previousTypeOutputMessage->type) {
                $changeSignature = new \stdClass;
                $changeSignature->messageType = 'output';
                $changeSignature->changeType = 'different parameter basic type';
                $changeSignature->previousType = $previousTypeOutputMessage->type;
                $changeSignature->currentType = $currentTypeOutputMessage->type;

                $this->appendSignatureChangeItem($response, $methodName, $changeSignature);
            }
        }

        if ((!$currentTypeOutputMessage->isBasic && $previousTypeOutputMessage->isBasic) || ($currentTypeOutputMessage->isBasic && !$previousTypeOutputMessage->isBasic)) {
            $changeSignature = new \stdClass;
            $changeSignature->messageType = 'output';
            $changeSignature->changeType = 'different parameter basic type';
            $changeSignature->previousType = $previousTypeOutputMessage->type;
            $changeSignature->currentType = $currentTypeOutputMessage->type;

            $this->appendSignatureChangeItem($response, $methodName, $changeSignature);
        }
        // END
    }

    protected function allParametersAreNillable($params, &$diffBackwardCompatible, $message)
    {
        if (!empty($params)) {
            foreach ($params as $paramName) {
                $diffBackwardCompatible = $diffBackwardCompatible & ($message[$paramName]->isNull);
            }
        }
    }

    protected function getStructureWithTypeLabelFromMessage($message)
    {
        $messageTypes = new \stdClass;
        foreach ($message as $label => $object) {
            $messageTypes->{$label} = $object->type;
        }

        return $messageTypes;
    }

    protected function checkSignaturesHaveTheSameStructure($currentWSDL, $previousWSDL, $currentParams, $currentInputMessage, $previousParams, $previousInputMessage, &$diffParameterStructure, &$parameterHaveSameStructure)
    {
        foreach ($currentParams as $index => $parameterName) {
            $currentParameterType = $this->getParameterStructure($currentInputMessage[$parameterName], $currentWSDL);

            $previousParameterName = $previousParams[$index];
            $previousParameterType = $this->getParameterStructure($previousInputMessage[$previousParameterName], $previousWSDL);

            if ($previousParameterType != $currentParameterType) {
                $diffParameterStructure[$index] = [
                    "current" => $currentParameterType,
                    "previous" => $previousParameterType
                ];

                $parameterHaveSameStructure = false;
            }
        }
    }

    protected function appendParameterStructure($wsdl, $parameterDetail, $parameterName, &$response)
    {
        $response->{$parameterName} = $parameterDetail;
        if (!$parameterDetail->isBasic) {
            $response->{$parameterName}->structure = $this->getParameterStructure($parameterDetail, $wsdl);
        }
    }
}
