<?php
namespace DokraApplication\api;

class Handler
{
    public $version;
    public $endpoint;

    public function setVersion($version)
    {
        $this->version = $version;
    }

    public function setEndpoint($endpoint)
    {
        $this->endpoint = $endpoint;
    }

    public function getAPI()
    {
        switch ($this->endpoint . '|' . $this->version) {
            case 'element|1.0':
                return new v1\element\API();
                break;

            case 'element|2.0':
                return new v2\element\API();
                break;
            
            default:
                break;
        }

        throw new \Exception("The requested API is not found [" . $this->endpoint . " version " . $this->version . "].", 5);
    }

    public function __call($methodName, $params = array())
    {
        $hash = array_shift($params);

        $api = $this->getAPI();
        $api->setHash($hash);


        return call_user_method_array($methodName, $api, $params);
    }
}
