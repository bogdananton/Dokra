<?php
namespace DokraApplication;

class JsonServer
{
    protected $handler;

    public function setObject($handler)
    {
        $this->handler = $handler;
    }

    public function handle($request = null) {
        if (is_null($request)) {
            $request = file_get_contents('php://input');
        }

        $request = json_decode($request, true);
        return call_user_method_array($request['method'], $this->handler, $request['params']);
    }
}
