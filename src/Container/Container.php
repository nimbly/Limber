<?php

namespace Limber\Container;

use Psr\Container\ContainerInterface;


class Container implements ContainerInterface
{
    /**
     * Singleton instance.
     *
     * @var Container
     */
    protected static $self;

    /**
     * Get/create container instance.
     *
     * @return static
     */
    public static function getInstance(): Container
    {
        if( empty(self::$self) ){
            self::$self = new static;
        }

        return self::$self;
    }

    /**
     * Container items
     *
     * @var array<string, mixed>
     */
    protected $items = [];

    /**
     * Set a container instance
     *
     * @param string $abstract
     * @param mixed $concrete
     * @return Container
     */
    public function set(string $abstract, $concrete): Container
    {
        $this->items[$abstract] = $concrete;
        return $this;
    }

    /**
     * Create a singleton builder.
     *
     * @param string $abstract
     * @param callable $builder
     * @return Container
     */
    public function singleton(string $abstract, callable $builder): Container
    {
        $this->items[$abstract] = new Singleton($builder);
        return $this;
    }

    /**
     * Create a factory builder.
     *
     * @param string $abstract
     * @param callable $builder
     * @return Container
     */
    public function factory(string $abstract, callable $builder): Container
    {
        $this->items[$abstract] = new Factory($builder);
        return $this;
    }

    /**
     * Get an instance from the container
     *
     * @param string $id
     * @return mixed
     */
    public function get($id)
    {
        if( !$this->has($id) ){
            throw new EntryNotFoundException;
        }

        $concrete = $this->items[$id];

        if( $concrete instanceof ContainerBuilder ){
            return $concrete->make();
        }

        return $concrete;        
    }

    /**
     * Does the container have this instance?
     *
     * @param string $id
     * @return boolean
     */
    public function has($id): bool
    {
        return array_key_exists($id, $this->items);
    }
}