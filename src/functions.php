<?php

use Limber\Application;
use Limber\Config;
use Limber\Container\Container;
use Symfony\Component\HttpFoundation\Response;

if( !function_exists('path') ){

    /**
     * @param string $relativePath
     * @return string
     */
    function path(string $relativePath): string
    {
        if( defined('APP_ROOT') ){
            return realpath(APP_ROOT . '/' . $relativePath);
        }

        return $relativePath;
    }
}

if( !function_exists('config') ){

    /**
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    function config(string $name = null, mixed $default = null)
    {
        return Container::getInstance()->get(Config::class)->get($name, $default);
    }
}


if( !function_exists('class_method') ){

    /**
     * @param $classMethod
     * @return callable
     * @throws ErrorException
     */
    function class_method(string $classMethod): callable
    {
        if( preg_match('/^([\\\d\w_]+)@([\d\w_]+)$/', $classMethod, $match) ){

            if( class_exists($match[1]) ){

                $instance = new $match[1];

                if( \method_exists($instance, $match[2]) ){
                    return [$instance, $match[2]];
                }
            }
        }

        throw new \ErrorException("Cannot resolve class method: {$classMethod}");
    }
}

if( !function_exists('redirect')){

    /**
     * @param string $url
     * @param int $code
     * @return Response
     */
    function redirect(string $url, int $code = \Symfony\Component\HttpFoundation\Response::HTTP_FOUND): Response
    {
        return new \Symfony\Component\HttpFoundation\Response(
            null,
            $code,
            ['Location' => $url]
        );
    }
}

if( !function_exists('parse_http_query') ){

    /**
     * @param string $string
     * @return array
     */
    function parse_http_query(string $string): array
    {
        $response = [];

        foreach( explode('&', $string) as $item ){
            list($key, $value) = explode('=', $item, 2);

            $response[$key] = urldecode($value);
        }

        return $response;
    }
}

if( !function_exists('is_env') ){

    /**
     * @param $environment
     * @throws \Exception
     * @return bool
     */
    function is_env(string $environment): bool
    {
        return getenv('APP_ENV') == $environment;
    }
}