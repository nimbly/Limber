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
     * Singleton pattern.
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
     * Container instances
     *
     * @var array
     */
    protected $instances = [];

    /**
     * Set a container instance
     *
     * @param string $id
     * @param mixed $value
     * @return void
     */
    public function set($id, $value)
    {
        $this->instances[$id] = $value;
    }

    /**
     * Get an instance from the container
     *
     * @param string $id
     * @return mixed
     */
    public function get($id)
    {
        if( $this->has($id) ){
            return $this->instances[$id];
        }

        throw new EntryNotFoundException;
    }

    /**
     * Does the container have this instance?
     *
     * @param string $id
     * @return boolean
     */
    public function has($id)
    {
        return array_key_exists($id, $this->instances);
    }
}