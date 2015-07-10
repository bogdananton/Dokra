<?php

$client = new JSONClient('http://application-1.dokra.dev/json-rpc/element/v1');

try
{
    $return = $client->getRegions('hashSSID0001', array('REGION1', 'REGION2'));
    print_r($return);

} catch(Exception $e) {
    print_r($e->getMessage());
}


class JSONClient
{
    protected $URL;
    protected $httpClient;
    protected $calls = 0;

    public function __construct($URL)
    {
        $this->URL = $URL;
    }

    public function prepareHttpClient()
    {
        if (!is_null($this->httpClient)) {
            return;
        }

        $httpClient = curl_init();
        curl_setopt($httpClient, CURLOPT_URL, $this->URL);
        curl_setopt($httpClient, CURLOPT_POST, 1);
        curl_setopt($httpClient, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($httpClient, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($httpClient, CURLOPT_HTTPHEADER, array(
            'Content-Type: text/plain',
            'Accept: text/plain'
        ));
        curl_setopt($httpClient, CURLOPT_VERBOSE, false);
        curl_setopt($httpClient, CURLOPT_PROXY, '');
        
        $this->httpClient = $httpClient;
    }

    public function __call($methodName, $arguments = array())
    {
        $this->prepareHttpClient();
        $request  = $this->createRequest($methodName, $arguments);
        return $this->request((object)$request);
    }

    public function createRequest($methodName, $arguments = array())
    {
        $jsonRpcRequest = array (
            'method'  => $methodName,
            'params'  => $arguments,
            'id'      => $this->calls++,
            'jsonrpc' => '1.0'
       );

        return $jsonRpcRequest;
    }

    public function request($request)
    {
        curl_setopt($this->httpClient, CURLOPT_POSTFIELDS, json_encode($request));
        $responseString = curl_exec($this->httpClient);

        $response = json_decode($responseString);

        $lastJsonError = json_last_error();
        if ($lastJsonError) {
            throw new \Exception('JSON decode has failed [' . $lastJsonError . '].');
        }

        if (isset($response->result)) {
            return $response->result;
        }
    
        throw new \Exception('There is a problem with the response [' . $responseString . '].');
    }
}
