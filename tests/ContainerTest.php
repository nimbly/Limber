<?php

namespace Limber\Tests;

use Limber\Container\Container;
use Limber\Router\LinearRouter;
use Limber\Router\RouterAbstract;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
    public function test_get_instance()
    {
        $container = Container::getInstance();
        $this->assertTrue($container instanceof Container);
    }

    public function test_set()
    {
        $container = Container::getInstance();
        $container->set(Container::class, $container);
        $this->assertTrue($container->get(Container::class) instanceof Container);
    }

    public function test_singleton_returns_same_object()
    {
        $container = Container::getInstance();
        $container->singleton(RouterAbstract::class, function(){
            return new LinearRouter;
        });

        $this->assertSame(
            $container->get(RouterAbstract::class),
            $container->get(RouterAbstract::class)
        );
    }

    public function test_factory_returns_different_object()
    {
        $container = Container::getInstance();
        $container->factory(RouterAbstract::class, function(){
            return new LinearRouter;
        });

        $this->assertNotSame(
            $container->get(RouterAbstract::class),
            $container->get(RouterAbstract::class)
        );
    }
}