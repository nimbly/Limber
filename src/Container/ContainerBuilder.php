<?php

namespace Limber\Container;

abstract class ContainerBuilder
{
    /**
     * Builder callable.
     *
     * @var callable
     */
    protected $builder;

    /**
     * Create container builder instance.
     *
     * @param callable $builder
     */
    public function __construct(callable $builder)
    {
        $this->builder = $builder;
    }

    /**
     * Make the instance.
     *
     * @return mixed
     */
    abstract public function make();
}