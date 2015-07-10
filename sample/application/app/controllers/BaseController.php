<?php
namespace DokraApplication\controllers;

abstract class BaseController
{
    public function call($methodName, $params)
    {
        $rClass = new \ReflectionObject($this);
        if ($rClass->hasMethod($methodName)) {
            $rMethod = $rClass->getMethod($methodName);
            if ($rMethod && $rMethod->isPublic()) {
                return call_user_method_array($methodName, $this, $params);
            }
        }

        throw new \Exception("Method not implemented.", 2);
    }

    public function view($viewName, $params = array(), $extension = 'php')
    {
        $filename = dirname(__FILE__) . '/../views/' . $viewName . '.' . $extension;
        if (file_exists($filename)) {
            return file_get_contents($filename);
        }

        throw new \Exception("View [" . $viewName . "] was not found.", 3);
    }

    public function wsdl($viewName)
    {
        header('Content-type: text/xml');
        return $this->view($viewName, array(), 'wsdl');
    }
}
