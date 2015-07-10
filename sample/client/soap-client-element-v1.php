<?php
ini_set("soap.wsdl_cache_enabled", 0);

$client = new SoapClient('http://application-1.dokra.dev/soap-rpc/element/v1', array(
    'cache_wsdl' => false
));

try
{
    $return = $client->getRegions('hashSSID0001', array('REGION1', 'REGION2'));
    print_r($return);

} catch(SoapFault $e) {
    print_r($e);
}
