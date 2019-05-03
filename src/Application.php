<?php

namespace Limber;

use Psr\Container\ContainerInterface;

class Application
{
     /**
     * Container instance
     *
     * @var ContainerInterface
     */
    protected $container;

    /**
     * 
     * Limber Framework Application constructor.
     * 
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Get a config key
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function config(string $key, $default = null)
    {
        return $this->container->get(Config::class)->get($key, $default);
    }

    /**
     * Bootstrap the application
     *
     * @return void
     */
    public function bootstrap(): void
    {
        foreach( $this->config('bootstrap', []) as $file ){
            $bootstrap = require_once(path($file));
            $bootstrap($this);
        }
    }

    /**
     * Get the container instance
     *
     * @return ContainerInterface
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }
    
    /**
     * Make call on the container instance
     *
     * @param string $method
     * @param array $params
     * @return mixed
     */
    public function __call(string $method, array $params)
    {
        return $this->container->{$method}(...$params);
    }
}