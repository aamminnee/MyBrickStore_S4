<?php
namespace App\Core;

use App\Controllers\ImagesController;

/**
 * Class main
 * 
 * Main router
 *
 ** Parses the url and dispatches the request to the appropriate controller
 ** Sets imagescontroller as the default landing page
 * 
 * @package App\Core
 */
class Main {

    /**
     * Starts the application routing process.
     *
     * @return void
     */
    public function start() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $uri = $_SERVER['REQUEST_URI'];
        if (!empty($uri) && $uri[-1] === "/") {
            $uri = substr($uri, 0, -1);
        }

        $params = explode('/', $_GET['p'] ?? '');

        if ($params[0] !== "") {
            $controllerName = '\\App\\Controllers\\' . ucfirst(array_shift($params)) . 'Controller';
            $method = isset($params[0]) ? array_shift($params) : 'index';
        } else {
            $controllerName = '\\App\\Controllers\\ImagesController';
            $method = 'index';
        }

        if (class_exists($controllerName)) {
            $controller = new $controllerName();
            if (method_exists($controller, $method)) {
                (isset($params[0])) ? call_user_func_array([$controller, $method], $params) : $controller->$method();
            } else {
                http_response_code(404);
                echo "La page recherchée n'existe pas";
            }
        } else {
            http_response_code(404);
            echo "La page recherchée n'existe pas";
        }
    }
}