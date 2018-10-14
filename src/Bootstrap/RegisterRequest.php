<?php

namespace Limber\Bootstrap;

use Limber\Application;
use Symfony\Component\HttpFoundation\Request;

class RegisterRequest implements BootstrapInterface
{
    public function bootstrap(Application $application)
    {
        $application->set(Request::class, Request::createFromGlobals());
    }
}