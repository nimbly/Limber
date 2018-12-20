<?php

namespace Limber\Container;

class Factory extends ContainerBuilder
{
    /**
     * Make a new instance.
     *
     * @return mixed
     */
    public function make()
    {
        return call_user_func($this->builder);
    }
}