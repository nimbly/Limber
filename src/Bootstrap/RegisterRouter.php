<?php

namespace Limber\Bootstrap;

use Limber\Application;
use Limber\Router\Router;

class RegisterRouter implements BootstrapInterface
{
    public function bootstrap(Application $application)
    {
        $router = new Router;

        foreach( $application->config('routes') as $route ){
            require_once APP_ROOT . "/{$route}";
        }

        $application->set(Router::class, $router);
    }
}