<?php

namespace Limber\Container;

use Psr\Container\ContainerInterface;


class Container implements ContainerInterface
{
    /**
     * Singleton instance.
     *
     * @var static
     */
    protected static $self;

    /**
     * Get/create container instance.
     *
     * @return static
     */
    public static function getInstance()
    {
        if( empty(self::$self) ){
            self::$self = new static;
        }

        return self::$self;
    }

    /**
     * Container items
     *
     * @var array
     */
    protected $items = [];

    /**
     * Set a container instance
     *
     * @param string $id
     * @param mixed $value
     * @return static
     */
    public function set($abstract, $concrete)
    {
        $this->items[$abstract] = $concrete;
        return $this;
    }

    /**
     * Create a singleton builder.
     *
     * @param string $abstract
     * @param callable $builder
     * @return static
     */
    public function singleton($abstract, callable $builder)
    {
        $this->items[$abstract] = new Singleton($builder);
        return $this;
    }

    /**
     * Create a factory builder.
     *
     * @param string $abstract
     * @param callable $builder
     * @return static
     */
    public function factory($abstract, callable $builder)
    {
        $this->items[$asbtract] = new Factory($builder);
        return $this;
    }

    /**
     * Get an instance from the container
     *
     * @param string $id
     * @return mixed
     */
    public function get($abstract)
    {
        if( !$this->has($abstract) ){
            throw new EntryNotFoundException;
        }

        $concrete = $this->items[$abstract];

        if( $concrete instanceof ContainerBuilder ){
            return $concrete->make();
        }

        return $concrete;        
    }

    /**
     * Does the container have this instance?
     *
     * @param string $abstract
     * @return boolean
     */
    public function has($abstract)
    {
        return array_key_exists($abstract, $this->items);
    }
}