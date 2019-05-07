<?php

namespace Limber\Tests;

use Error;
use Limber\Application;
use Limber\Container\Container;
use PHPUnit\Framework\TestCase;

class ApplicationTest extends TestCase
{
    public function test_constructor_accepts_container_instance()
    {
        $container = new Container;
        $application = new Application($container);
        $this->assertSame($container, $application->getContainer());
    }

    public function test_catchall_calls_container_instance()
    {
        $this->expectException(Error::class);
        $application = new Application(new Container);
        $application->foo();
    }
}