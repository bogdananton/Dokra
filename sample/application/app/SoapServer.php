<?php
namespace DokraApplication;

class SoapServer extends \SoapServer 
{
    private $Headers = array();
    
    public function getHeaders() {
        return $this->Headers;
    }
    
    function __construct($wsdl, $options = array()) {
        parent::__construct($wsdl, $options);
    }

    public function handle($request = null) {
        if (is_null($request)) {
            $request = file_get_contents('php://input');
        }

        if (!empty($request)) {
            $DOM = new \DOMDocument('1.0', 'UTF-8');
            $DOM->preserveWhiteSpace = false;
            $status = @$DOM->loadXML($request);

            if (!$status) {
                $request = iconv('iso-8859-1', 'utf-8', $request);
                $status = @$DOM->loadXML($request);
            }
            if ($status) {
                $HeaderNodeList = $DOM->getElementsByTagNameNS('http://schemas.xmlsoap.org/soap/envelope/', 'Header');
                foreach ($HeaderNodeList as $HeaderNode) {
                    foreach ($HeaderNode->childNodes as $soapHeader) {
                        $content = domnode_to_array($soapHeader);
                        $this->Headers[$soapHeader->nodeName] = $content;
                    }
                    $HeaderNode->parentNode->removeChild($HeaderNode);
                }
                $request = $DOM->saveXML();
            }
        }
        return parent::handle($request);
    }
}
