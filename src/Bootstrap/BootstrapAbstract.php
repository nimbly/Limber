<?php

namespace Limber\Bootstrap;


use Limber\Application;

abstract class BootstrapAbstract
{
    abstract public function bootstrap(Application $application);
}