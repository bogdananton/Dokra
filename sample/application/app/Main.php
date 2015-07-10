<?php
namespace DokraApplication;
use DokraApplication\controllers\SoapController;

class Main
{
    protected $routes = array();

    public function __construct()
    {
        $this->routes = require_once("config/routes.php");
    }

    public function run()
    {
        $uri = $_SERVER['REQUEST_URI'];
        $uri = substr($uri, (strpos($uri, '/') === 0 ? 1 : 0));

        $paramsToMatch = array();
        $controllerRaw = false;

        foreach ($this->routes as $key => $value) {
            if ($value['method'] == $_SERVER['REQUEST_METHOD']) {
            // if (true) {
                if ($value['url']) {
                    preg_match_all("/\{([\w\_]+)\}/", $value['url'], $matches);
                    if ($matches && !empty($matches) && !empty($matches[0])) {
                        $url = $value['url'];

                        foreach ($matches[0] as $placeholder) {
                            $url = str_replace($placeholder, '([\w\_\-]+)', $url);
                        }

                        $pattern = "/" . str_replace('/', '\/', $url) . "/";
                        preg_match($pattern, $uri, $urlMatches);

                        if ($urlMatches && !empty($urlMatches[0])) {
                            // $testUrlFinal = $uri;
                            foreach ($matches[1] as $index => $param) {
                                $paramsToMatch[$param] = $urlMatches[$index + 1]; 
                                // $testUrlFinal = str_replace('{' . $param . '}', $urlMatches[$index + 1], $testUrlFinal);
                            }

                            error_log(json_encode($_SERVER));

                            $controllerRaw = $value['use'];
                            break;
                        }
                    } else if ($uri == $value['url']) {
                        $controllerRaw = $value['use'];
                        break;
                    }
                }
            }
        }

        if ($controllerRaw && !empty($controllerRaw)) {
            $controllerRaw = explode('@', $controllerRaw);
            $controllerClass = 'DokraApplication\controllers\\' . $controllerRaw[0];
            $controllerMethod = $controllerRaw[1];

            $class = new $controllerClass();
            return $class->call($controllerMethod, $paramsToMatch);
        }

        throw new \Exception("The route is not defined.", 1);
    }
}
