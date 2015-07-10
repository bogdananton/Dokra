<?php
ini_set("soap.wsdl_cache_enabled", 0);

$client = new SoapClient('http://application-1.dokra.dev/soap-rpc/element/v2', array(
    'cache_wsdl' => false
));

try
{
    // $return = $client->getRegion('hashSSID0001', 'REGION1');
    $return = $client->getRegions('hashSSID0001', array('REGION1', 'REGION2'));
    print_r($return);

} catch(SoapFault $e) {
    print_r($e);
}
