<?php

namespace Limber\Bootstrap;

use Limber\Application;

interface BootstrapInterface
{
    /**
     * @param Application $application
     * @return void
     */
    public function bootstrap(Application $application);
}