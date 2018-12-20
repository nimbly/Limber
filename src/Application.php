<?php

namespace Limber;

class Application
{
     /**
     * Container instance
     *
     * @var Psr\Container\ContainterInterface
     */
    protected $container;

    /**
     * 
     * Limber Framework Application constructor.
     * 
     * @param Psr\Container\ContainerInterface $container
     */
    public function __construct($container)
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
    public function config($key, $default = null)
    {
        return $this->get(Config::class)->get($key, $default);
    }

    /**
     * Bootstrap the application
     *
     * @return void
     */
    public function bootstrap()
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
    public function getContainer()
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
    public function __call($method, array $params)
    {
        return $this->container->{$method}(...$params);
    }
}