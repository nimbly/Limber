<?php

namespace Limber\Container;

class Singleton extends ContainerBuilder
{
    /**
     * Singleton instance.
     *
     * @var mixed
     */
    protected $instance;

    /**
     * Get or make the singleton instance.
     *
     * @return mixed
     */
    public function make()
    {
        if( !$this->instance ){
            $this->instance = call_user_func($this->builder);
        }

        return $this->instance;
    }
}