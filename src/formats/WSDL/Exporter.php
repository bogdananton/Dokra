<?php
namespace Dokra\formats\WSDL;


use Dokra\Application;
use Dokra\base\Config;

class Exporter
{
    use Config;

    protected $item;
    protected $scope;
    protected $templateContents;

    public function fromPHP($item)
    {
        $this->scope = 'PHP';
        $this->item = $item;
        $this->templateContents = '';
        return $this;
    }

    protected function getTemplate($template = 'layout', $data = [])
    {
        if (count($data) > 0) {
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    $value = implode(PHP_EOL, $value);
                }
                $$key = $value;
            }
        }

        ob_start();
        include __DIR__ . '/template/' . $template . '.xml';
        return ob_get_clean();
    }

    public function run()
    {
        // @todo Handle other input formats.
        if ($this->scope === 'PHP' && count($this->item) > 0 && strlen($this->scope) > 0) {
            // execute

            $code = $this->item->source->endpoint;
            $version = $this->item->source->version;

            $complexTypeList = [];
            $messageList = [];
            $operationMessageList = [];
            $operationBindingsList = [];

            $gatherBasicArrayTypes = [];
            $gatherComplexArrayTypes = [];
            $gatherComplexTypes = [];

            foreach ($this->item->entry->methods as $entry) {
                $operationMessageList[] = $this->getTemplate('operationMessage', [
                    'name' => $entry->method
                ]);

                $operationBindingsList[] = $this->getTemplate('operationBinding', [
                    'name' => $entry->method,
                    'endpointCode' => $code
                ]);

                // requests
                $partList = [];

                // add auth layer via first param (session hash)
                $partList[] = $this->getTemplate('part', [
                    'label' => 'Hash',
                    'type' => 'xsd:string',
                    'nillable' => false
                ]);

                if (count($entry->processed->Params) > 0) {
                    foreach ($entry->processed->Params as $param) {
                        $type = strtolower($param->type);
                        $responseIsBasic = in_array($type, ['string', 'bool', 'boolean', 'integer'], true);
                        $isArray = ($param->isArray);

                        if ($responseIsBasic) {
                            $responseClass = $isArray ? ('typens:' . ucwords($type) . 'Array') : ('xsd:'. $type);
                            if ($isArray) {
                                $gatherBasicArrayTypes[] = $type;
                            }

                        } else {
                            $className = $param->details->classDetails->className;
                            $responseClass = 'typens:' . $className;

                            if ($isArray) {
                                $responseClass .= 'Array';
                                $gatherComplexArrayTypes[] = $className;

                            } else {
                                $gatherComplexTypes[$className] = $param;
                            }
                        }

                        /** <part name="<?=$label?>" type="<?=$type?>" <?=($nillable ? 'nillable="true"' : '')?> /> */
                        $partList[] = $this->getTemplate('part', [
                            'label' => $param->name,
                            'type' => $responseClass,
                            'nillable' => !empty($param->default)
                        ]);
                    }
                }

                $partList = implode(PHP_EOL . "        ", $partList);

                $messageList[] = $this->getTemplate('message', [
                    'messageType' => $entry->method . 'Request',
                    'partList' => $partList
                ]);

                // responses
                $type = strtolower($entry->processed->Return[0]->type);
                $responseIsBasic = in_array($type, ['string', 'bool', 'boolean', 'integer'], true);
                $isArray = ($entry->processed->Return[0]->isArray);

                if ($responseIsBasic) {
                    $responseClass = $isArray ? ('typens:' . ucwords($type) . 'Array') : ('xsd:'. $type);
                    if ($isArray) {
                        $gatherBasicArrayTypes[] = $type;
                    }

                } else {
                    $className = $entry->processed->Return[0]->details->classDetails->className;
                    $responseClass = 'typens:' . $className;

                    if ($isArray) {
                        $responseClass .= 'Array';
                        $gatherComplexArrayTypes[] = $className;

                    } else {
                        $gatherComplexTypes[$className] = $entry->processed->Return[0];
                    }
                }

                $partList = $this->getTemplate('part', [
                    'label' => ucwords(end(explode('\\', $type))),
                    'type' => $responseClass,
                    'nillable' => false
                ]);

                $messageList[] = $this->getTemplate('message', [
                    'messageType' => $entry->method . 'Response',
                    'partList' => $partList
                ]);
            }

            $complexTypeList = [];

            if (count($gatherBasicArrayTypes) > 0) {
                foreach ($gatherBasicArrayTypes as $arrayType) {
                    $complexTypeList[] = $this->getTemplate('complexType.array', [
                        'label' => ucwords($arrayType),
                        'type' => 'xsd:' . $arrayType
                    ]);
                }
            }

            if (count($gatherComplexArrayTypes) > 0) {
                foreach ($gatherComplexArrayTypes as $arrayType) {
                    $complexTypeList[] = $this->getTemplate('complexType.array', [
                        'label' => $arrayType,
                        'type' => 'typens:' . $arrayType
                    ]);
                }
            }

            if (count($gatherComplexTypes) > 0) {
                foreach ($gatherComplexTypes as $type => $param) {
                    $partList = [];

                    foreach ($param->details->attributes as $attribute) {
                        // @todo test and fix / warn about invalid basic types (here or in importer)
                        if ($attribute->type === 'bool') {
                            $attribute->type = 'boolean';
                        }

                        // @todo handle complex type detection and fill abstract or sub-items
                        $partList[] = $this->getTemplate('part', [
                            'label' => $attribute->name,
                            'type' => 'xsd:' . $attribute->type,
                            'nillable' => !empty($attribute->default)
                        ]);
                    }

                    $complexTypeList[] = $this->getTemplate('complexType.custom', [
                        'label'       => $type,
                        'abstract'    => false,
                        'elementList' => implode(PHP_EOL . '                    ', $partList) . PHP_EOL
                    ]);
                }
            }

            $URL = $this->getStorage()->get(Application::WSDL_ENDPOINT_URL_LOCATION);
            $URL = sprintf($URL, $code, $version);

            $data = [
                'endpointCode'          => $code,
                'endpointName'          => ucwords(str_replace('-', '', $code)),
                'complexTypeList'       => $complexTypeList,
                'location'              => $URL,
                'messageList'           => $messageList,
                'operationMessageList'  => $operationMessageList,
                'operationBindingsList' => $operationBindingsList
            ];

            $this->templateContents = $this->getTemplate('layout', $data);

            $file = sprintf(Application::WSDL_ENDPOINT_OUTPUT, $code, $version, 'output.xml');
            $this->getStorage()->set($file, $this->templateContents);

        } else {
            throw new \InvalidArgumentException('No working endpoint item found for processing.');
        }

        return $this;
    }
}