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

        print_r($this->hashMaps);
    }

    protected function getMethodsFromWSDL($WSDLEntry)
    {
        $response = [];

        foreach ($WSDLEntry->methodOperations as $operation) {
            $response[$operation->methodName] = $operation;
        }

        return $response;
    }

    protected function extractItemChanges($currentWSDL, $previousWSDL)
    {
        $changes = new \stdClass();

        $changes->methodsAdded = $this->extractItemChangesMethodsAddedOrRemoved($currentWSDL, $previousWSDL);
        $changes->methodsRemoved = $this->extractItemChangesMethodsAddedOrRemoved($previousWSDL, $currentWSDL);
//        $changes->methodsSignatureChanges = null;
//        $changes->methodsOutputResponse = null;
//        $changes->methodsInputResponse = null;
//        $changes->objectsAdded = null;
//        $changes->objectsRemoved = null;
//        $changes->objectsChanged = null;
//        $changes->documentationChanged = null;

//        print_r($currentWSDL);
        return $changes;
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
        sort($methodNames);

        if (!empty($methodNames)) {
            return $methodNames;
        }
    }
}
