<?php

if( !function_exists('config') ){

    /**
     * @param mixed $key
     * @param mixed $default
     * @return mixed
     */
    function config($key, $default = null)
    {
        return \Limber\Application::getInstance()->config($key, $default);
    }

}

if( !function_exists('class_method') ){

    /**
     * @param $classMethod
     * @return array
     * @throws ErrorException
     */
    function class_method($classMethod)
    {
        if( preg_match('/^([\\\d\w_]+)@([\d\w_]+)$/', $classMethod, $match) ){

            if( class_exists($match[1]) ){
                return [new $match[1], $match[2]];
            }

        }

        throw new \ErrorException("Cannot resolve class method: {$classMethod}");
    }

}