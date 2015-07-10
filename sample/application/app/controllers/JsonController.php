<?php
namespace DokraApplication\controllers;

class JsonController extends BaseController
{
    public function post_index($endpoint, $version)
    {
        $version = number_format($version, 1);

        $Handler = new \DokraApplication\api\Handler();
        $Handler->setEndpoint($endpoint);
        $Handler->setVersion($version);

        $jsonServer = new \DokraApplication\JsonServer();
        $jsonServer->setObject($Handler);

        $return = new \stdClass;
        $return->success = true;
        $return->message = null;
        $return->result = null;

        try {
            $return->result = $jsonServer->handle();
            
        } catch (\Exception $e) {
            $return->success = false;
            $return->message = $e->getMessage();
        }

        echo json_encode($return);
        exit();
    }
}
