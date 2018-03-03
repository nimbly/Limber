<?php

namespace Limber;

use Adbar\Dot;
use Limber\Kernel\Kernel;
use Limber\core\HttpKernel;


class Application
{
    /** @var Dot */
    protected $config;

    /** @var Application */
    protected static $self;

    /**
     * Create a new Limber application instance
     */
    public function __construct()
    {
        require 'functions.php';
        $this->config = new Dot;
    }

    /**
     * Get Application instance
     *
     * @return Application
     */
    public static function getInstance()
    {
        if( self::$self === null ){
            throw new \ErrorException("Application has not been instantiated yet");
        }

        return self::$self;
    }

    /**
     * Load config files
     *
     * @param array $files
     * @return void
     */
    public function loadConfig(array $files)
    {
        foreach( $files as $file ){
            $data = require $file;
            foreach($data as $key => $value ){
                $this->config->add($key, $value);
            }
        }
    }

    /**
     * Load the bootstrappers
     *
     * @param array $files
     * @return void
     */
    public function loadBootstrap(array $files)
    {
        foreach( $files as $bootstrapper ){
            (new $bootstrapper)->bootstrap($this);
        }
    }

    /**
     * Get a config value
     *
     * @param mixed $key
     * @param mixed $default
     * @return mixed
     */
    public function config($key, $default = null)
    {
        return $this->config->get($key, $default);
    }


    /**
     * Run the application with the given Kernel
     *
     * @param Kernel $kernel
     * @return mixed
     */
    public function run(Kernel $kernel)
    {
        return $kernel();
    }
}