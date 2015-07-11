<?php
namespace Dokra\formats\WSDL;

class VersionChanges
{
    protected $entries;
    protected $hashMaps = [];
    protected $htmlReport = "";

    public function from($wsdlInterfaces)
    {
        $this->entries = $wsdlInterfaces;
        return $this;
    }

    public function run()
    {
        $this->makeInterfacesHashMapsIndexes();
        $this->extractAllChanges();
        $this->createHTML();

        return $this;
    }

    protected $currentVersion;
    protected $currentEndpoint;

    protected function getPreviousVersion()
    {
        $previousVersion = null;
        if (array_key_exists($this->currentEndpoint, $this->hashMaps)) {
            foreach ($this->hashMaps[$this->currentEndpoint] as $version => $entry) {
                if ($version === $this->currentVersion) {
                    break;
                }
                $previousVersion = $version;
            }
        }
        return $previousVersion;
    }

    protected function getPreviousWSDL()
    {
        $previousVersion = $this->getPreviousVersion();
        if (!is_null($previousVersion)) {
            return $this->getWSDL($this->currentEndpoint, $previousVersion);
        }
    }

    protected function getCurrentWSDL()
    {
        return $this->getWSDL($this->currentEndpoint, $this->currentVersion);
    }

    protected function createHTML()
    {
        $html = "";

        foreach ($this->hashMaps as $endpoint => $endpointEntry) {
            $endpointString = str_replace(' ', '-', ucwords(strtolower(str_replace(['-', '_'], ' ', $endpoint))));
            $this->currentEndpoint = $endpoint;

            foreach ($endpointEntry as $version => $endpointItemChanges) {
                if (null === $endpointItemChanges) {
                    continue; // ignore first version
                }

                $html .= "# " . $endpointString . ' ' . $version . " WSDL\n\n";

                $this->currentVersion = $version;

                foreach ($endpointItemChanges as $changeGroup => $changes) {
                    $changeGroupLabel = "## " . ucfirst(strtolower(implode(" ", preg_split('/(?=[A-Z])/', $changeGroup))));

                    if (is_array($changes) && is_string(end($changes)) && !empty($changes)) {
                        // listing
                        $html .= $changeGroupLabel . ":\n- " . implode("\n- ", $changes) . "\n\n";

                    } else if (!is_null($changes)) {
                        $html .= $changeGroupLabel . ":\n\n";
                        $count = 0;
                        foreach ($changes as $methodName => $methodChanges) {
                            $count++;
                            $indexLiteral = 'a';
                            $html .= $count . "). Method " . $methodName . ":\n";

                            foreach ($methodChanges as $methodChange) {
                                $html .= "\n" . $indexLiteral . "). " . $methodChange->changeType . " on " . $methodChange->messageType . "\n";
                                $html .= $this->getSignatureChangeHTML($methodChange);
                                $indexLiteral++;
                            }
                            $html .= "\n\n";
                        }
                    }
                }
                $html .= "\n\n";
            }
        }

        $this->htmlReport = $html;
    }

    protected function getSignatureChangeHTMLInputStructure($methodChange)
    {
        $return = "";

        if (isset($methodChange->added) && !empty($methodChange->added)) {
            foreach ($methodChange->added as $parameterPosition => $parameterName) {
                if (isset($methodChange->removed) && isset($methodChange->removed[$parameterPosition])) {
                    $return .= "Parameter [" . $parameterName . "] replaced [" . $methodChange->removed[$parameterPosition] . "] on position " . ($parameterPosition + 1) . ".\n";

                } else {
                    $return .= "Parameter [" . $parameterName . "] was added on position " . ($parameterPosition + 1) . ".\n";
                }
            }
        }

        if (isset($methodChange->removed) && !empty($methodChange->removed)) {
            foreach ($methodChange->removed as $parameterPosition => $parameterName) {
                if (!in_array($parameterName, $methodChange->added) && !isset($methodChange->added[$parameterPosition])) {
                    $return .= "Parameter [" . $parameterName . "] was removed; was found at position " . ($parameterPosition + 1) . ".\n";
                }
            }
        }

        if (isset($methodChange->parameters) && !empty($methodChange->parameters)) {
            foreach ($methodChange->parameters as $position => $parameterName) {
                if (isset($methodChange->diffParameterStructure) && isset($methodChange->diffParameterStructure[$position])) {
                    $structure = $methodChange->diffParameterStructure[$position];
                    $methodChangeNew = new \stdClass;
                    $methodChangeNew->structure = [$structure];

                    $extraDetails = $this->getSignatureChangeHTMLOutputStructure($methodChangeNew);
                    $extraDetails = trim($extraDetails);

                    if (!empty($extraDetails)) {
                        $extraDetails = "  " . implode("\n  ", explode("\n", $extraDetails));
                        $extraDetails = "Parameter [" . $parameterName . "] has changed:\n" . $extraDetails;
                        $return .= $extraDetails;
                    }
                }
            }
        }

        return $return;
    }

    protected function getSignatureChangeHTMLOutputStructure($methodChange)
    {
        $return = "";

        $structure = (object)$methodChange->structure[0];

        $structure = clone($structure);
        if (isset($structure->current)) {
            if (is_array($structure->current) != is_array($structure->previous)) {
                if (is_array($structure->current)) {
                    $return .= "The method returned an array and now it doesn't.\n";
                } else {
                    $return .= "The method didn't returned an array of objects and now it does.\n";
                }
                return $return;
            } else {
                if (is_array($structure->current)) {
                    if (count($structure->current) > 1) {
                        print_r($structure->current);
                        die("More than one current structure array item.");
                    }
                    $structure->current = $structure->current[0];
                }
                if (is_array($structure->previous)) {
                    if (count($structure->previous) > 1) {
                        print_r($structure->previous);
                        die("More than one previous structure array item.");
                    }
                    $structure->previous = $structure->previous[0];
                }
            }
        }

        if (isset($structure->current) && (is_array($structure->current) || is_object($structure->current))) {
            $addedKeys = array_diff(array_keys((array)$structure->current), array_keys((array)$structure->previous));
            $removedKeys = array_diff(array_keys((array)$structure->previous), array_keys((array)$structure->current));

            if (!empty($addedKeys)) {
                $return .= "Added attribute key" . (count($addedKeys) > 1 ? 's' : '') . ": " . implode(", ", $addedKeys) . "\n";
            }

            if (!empty($removedKeys)) {
                $return .= "Removed attribute key" . (count($removedKeys) > 1 ? 's' : '') . ": " . implode(", ", $removedKeys) . "\n";
            }

            $nowNillable = [];
            $nowMandatory = [];

            foreach ($structure->current as $key => $currentStructure) {
                if (isset($structure->previous->{$key})) {
                    $previousWSDL = $this->getPreviousWSDL();
                    $currentWSDL = $this->getCurrentWSDL();

                    if ($previousWSDL && $currentWSDL) {
                        $currentStructureDetail = $this->getParameterStructure($currentStructure, $currentWSDL);

                        $previousStructure = $structure->previous->{$key};
                        $previousStructureDetail = $this->getParameterStructure($previousStructure, $previousWSDL);

//                        var_dump($currentStructure);
//                        var_dump($previousStructure);
//                        var_dump($currentStructureDetail);
//                        var_dump($previousStructureDetail);
//                        die();

                        if ($currentStructure->isNillable != $previousStructure->isNillable) {
                            if ($currentStructure->isNillable) {
                                $nowNillable[] = $key;
                            } else {
                                $nowMandatory[] = $key;
                            }
                        }

                        if ($currentStructureDetail != $previousStructureDetail) {
                            $return .= "The structure for the [" . $key . "] attribute has changed.";

                            if (is_scalar($currentStructureDetail) && is_scalar($previousStructureDetail)) {
                                $return .= " Current type is [" . $currentStructureDetail . "], previous type was [" . $previousStructureDetail . "].";
                            }

                            $return .= "\n";
                        }
                    } else {
                        var_dump($this->currentEndpoint . ' ' . $this->currentVersion);
                        var_dump($this->getPreviousVersion());
                        die();
                    }
                } else {
                    // was added so no structure change check is required
                }
            }

            if (!empty($nowNillable)) {
                $return .= "The " . implode(', ', $nowNillable) . ' attribute key' . (count($nowNillable) > 1 ? 's are' : ' is') . " now nillable.\n";
            }

            if (!empty($nowMandatory)) {
                $return .= "The " . implode(', ', $nowMandatory) . ' attribute key' . (count($nowMandatory) > 1 ? 's are' : ' is') . " now mandatory.\n";
            }

        } else {
            // @todo catch this
//            print_r($structure);
        }

//        die();

        return $return;
    }

    protected function getSignatureChangeHTML($methodChange)
    {
        $response = null;

        if ($methodChange->messageType == 'output') {
            switch ($methodChange->changeType) {
                default:
                case 'parameter structure changed':
                    $response = $this->getSignatureChangeHTMLOutputStructure($methodChange);
//                    print_r($methodChange);
                    break;
            }
        } else if ($methodChange->messageType == 'input') {
            switch ($methodChange->changeType) {
                default:
                case 'parameter structure changed':
                case 'different parameter names';
                case 'different parameter count':
                    $response = $this->getSignatureChangeHTMLInputStructure($methodChange);
//                    print_r($methodChange);
                    break;
            }
        }

        $response = trim($response);
        if (empty($response)) {
            return print_r($methodChange, 2);
        } else {
            $response .= "\n";
        }

        return $response;
    }

    public function getHTML()
    {
        return $this->htmlReport;
    }

    public function getJSON()
    {
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
                    $this->hashMaps[$ep][$v] = $this->extractItemChanges($currentWSDL, $previousWSDL);
                } else {
                    $this->hashMaps[$ep][$v] = null; // will be used only for previous
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

        $changes->methodsAdded = $this->extractItemChangesMethodsAddedOrRemoved($currentWSDL, $previousWSDL);
        $changes->methodsRemoved = $this->extractItemChangesMethodsAddedOrRemoved($previousWSDL, $currentWSDL);
        $changes->methodsSignatureChanges = $this->extractItemChangesMethodSignatures($currentWSDL, $previousWSDL);
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
                $this->checkSignatureForBasicTypeMsgs($currentTypeInputMessage, $previousTypeInputMessage, $methodName, $currentTypeOutputMessage, $previousTypeOutputMessage, $response);

                // check output
                $diffOutputStructure = [];
                $outputHasSameStructure = true;

                $currentOutput = $currentWSDL->methodMessages[$currentTypeOutputMessage->type];
                $previousOutput = $previousWSDL->methodMessages[$previousTypeOutputMessage->type];
                $currentOutputLabels = array_keys($currentOutput);
                $previousOutputLabels = array_keys($previousOutput);

                $this->checkSignaturesHaveTheSameStructure($currentWSDL, $previousWSDL, $currentOutputLabels, $currentOutput, $previousOutputLabels, $previousOutput, $diffOutputStructure, $outputHasSameStructure);

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

    protected function getWSDL($endpoint, $version)
    {
        foreach ($this->entries as $index => $entry) {
            $entryVersion = $entry->source->version;
            $entryEndpoint = $entry->source->endpoint;

            if ($endpoint == $entryEndpoint && $version == $entryVersion) {
                return $entry;
            }
        }
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

    protected function checkSignatureForBasicTypeMsgs($currentTypeInMsg, $previousTypeInputMessage, $methodName, $currentTypeOutMsg, $prevTypeOutMsg, &$response)
    {
        // BEGIN: the scenario below should not happen, but we'll leave here just in case and for debugging
        if ($currentTypeInMsg->isBasic && $previousTypeInputMessage->isBasic) {
            if ($currentTypeInMsg->type != $previousTypeInputMessage->type) {
                $sigChange = new \stdClass;
                $sigChange->messageType = 'input';
                $sigChange->changeType = 'different parameter basic type';
                $sigChange->previousType = $previousTypeInputMessage->type;
                $sigChange->currentType = $currentTypeInMsg->type;

                $this->appendSignatureChangeItem($response, $methodName, $sigChange);
            }
        }

        if ((!$currentTypeInMsg->isBasic && $previousTypeInputMessage->isBasic) || ($currentTypeInMsg->isBasic && !$previousTypeInputMessage->isBasic)) {
            $sigChange = new \stdClass;
            $sigChange->messageType = 'input';
            $sigChange->changeType = 'different parameter basic type';
            $sigChange->previousType = $previousTypeInputMessage->type;
            $sigChange->currentType = $currentTypeInMsg->type;

            $this->appendSignatureChangeItem($response, $methodName, $sigChange);
        }

        if ($currentTypeOutMsg->isBasic && $prevTypeOutMsg->isBasic) {
            if ($currentTypeOutMsg->type != $prevTypeOutMsg->type) {
                $sigChange = new \stdClass;
                $sigChange->messageType = 'output';
                $sigChange->changeType = 'different parameter basic type';
                $sigChange->previousType = $prevTypeOutMsg->type;
                $sigChange->currentType = $currentTypeOutMsg->type;

                $this->appendSignatureChangeItem($response, $methodName, $sigChange);
            }
        }

        if ((!$currentTypeOutMsg->isBasic && $prevTypeOutMsg->isBasic) || ($currentTypeOutMsg->isBasic && !$prevTypeOutMsg->isBasic)) {
            $sigChange = new \stdClass;
            $sigChange->messageType = 'output';
            $sigChange->changeType = 'different parameter basic type';
            $sigChange->previousType = $prevTypeOutMsg->type;
            $sigChange->currentType = $currentTypeOutMsg->type;

            $this->appendSignatureChangeItem($response, $methodName, $sigChange);
        }
        // END
    }

    protected function allParametersAreNillable($params, &$diffBackCompatible, $message)
    {
        if (!empty($params)) {
            foreach ($params as $paramName) {
                $diffBackCompatible = $diffBackCompatible & ($message[$paramName]->isNull);
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

    protected function checkSignaturesHaveTheSameStructure($currentWSDL, $previousWSDL, $currentParams, $currentInput, $previousParams, $prevInput, &$diffParamStructure, &$parameterHaveSameStructure)
    {
        foreach ($currentParams as $index => $parameterName) {
            $currentParamType = $this->getParameterStructure($currentInput[$parameterName], $currentWSDL);

            $prevParamName = $previousParams[$index];
            $prevParamType = $this->getParameterStructure($prevInput[$prevParamName], $previousWSDL);

            if ($prevParamType != $currentParamType) {
                $diffParamStructure[$index] = [
                    "current" => $currentParamType,
                    "previous" => $prevParamType
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
