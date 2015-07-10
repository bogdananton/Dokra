<?php
namespace DokraApplication\controllers;

class SoapController extends BaseController
{
    public function get_rpc($endpoint, $version)
    {
        return $this->get('rpc', $endpoint, $version);
    }

    // public function get_literal($endpoint, $version)
    // {
    //     return $this->get('literal', $endpoint, $version);
    // }

    public function get($type, $endpoint, $version)
    {
        echo $this->wsdl('soap-' . $type . '/' . $endpoint . '/' . $endpoint . '-' . number_format($version, 1));
    }

    public function post_rpc($endpoint, $version)
    {
        return $this->post('rpc', $endpoint, $version);
    }

    // public function post_literal($endpoint, $version)
    // {
    //     return $this->post('literal', $endpoint, $version);
    // }

    protected function post($type, $endpoint, $version)
    {
        $version = number_format($version, 1);
        $wsdl = dirname(__FILE__) . '/../views/soap-' . $type . '/' . $endpoint . '/' . $endpoint . '-' . $version . '.wsdl';

        if (file_exists($wsdl)) {
            $Handler = new \DokraApplication\api\Handler();
            $Handler->setEndpoint($endpoint);
            $Handler->setVersion($version);

            $SoapServer = new \DokraApplication\SoapServer($wsdl, array('cache_wsdl' => false));
            $SoapServer->setObject($Handler);
            $SoapServer->handle();
            return;
        }

        throw new \Exception("WSDL file not found.", 4);
    }
}
