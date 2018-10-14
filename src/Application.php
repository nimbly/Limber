<?php

namespace Limber;

use Adbar\Dot;
use Limber\Container\EntryNotFoundException;
use Psr\Container\ContainerInterface;
use Limber\Bootstrap\RegisterRequest;
use Limber\Bootstrap\RegisterRouter;

class Application implements ContainerInterface
{
    /**
     * Config data
     *
     * @var Dot
     */
    protected $config;

    /**
     * Framework required bootstraps
     *
     * @var array
     */
    protected $bootstrap = [
        RegisterRequest::class,
        RegisterRouter::class,
    ];

    /**
     * Container instances
     *
     * @var array
     */
    protected $instances = [];

    public function __construct()
    {
        $this->config = new Dot;
    }

    /**
     * Get a config key
     * 
     * This method attempts to lazy-load config files until needed.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function config($key, $default = null)
    {
        // Attempt to lazy load
        if( $this->config->has($key) == false ){

            if( preg_match("/^([^\.]+)\.?/", $key, $match) ){

                // Save the key
                $key = $match[1];

                // Build the file path
                $file = APP_ROOT . "/config/{$key}.php";
    
                // Check for file's existence
                if( file_exists($file) === false ){
                    throw new \Exception("Config file not found: {$file}");
                }
    
                // Pull config file in and add values into master config
                $config = require_once $file;
                $this->config->add([$key => $config]);

            }

        }

        return $this->config->get($key, $default);
    }

    /**
     * Bootstrap the application
     *
     * @return void
     */
    public function bootstrap()
    {
        $bootstraps = array_merge(
            $this->bootstrap,
            $this->config('bootstrap', [])
        );

        foreach( $bootstraps as $bootstrap ){
            (new $bootstrap)->bootstrap($this);
        }
    }

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

        throw new EntryNotFoundException();
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