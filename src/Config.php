<?php

namespace Limber;


class Config
{
    /**
     * Config structure.
     *
     * @var array<string, mixed>
     */
    protected $items = [];

    /**
     * Resolve the flattened key into the actual value.
     *
     * @param string $key
     * @throws \Exception
     * @return mixed
     */
    protected function resolve(string $key)
    {
        // Break the dotted notation keys into its parts
        $parts = explode(".", $key);

        // Set the pointer at the root of the items array
        $pointer = &$this->items;

        /**
         * 
         * Loop through all the parts and see if the key exists.
         * 
         */
        foreach( $parts as $part ){

            if( array_key_exists($part, $pointer) === false ){
                throw new \Exception("Config key {$key} not found.");
            }
            
            $pointer = &$pointer[$part];
        }

        return $pointer;
    }

    /**
     * See if config has given key.
     *
     * @param string $key
     * @return boolean
     */
    public function has(string $key): bool
    {
        try {

            $this->resolve($key);

        } catch( \Exception $exception ){

            return false;
        }

        return true;
    }

    /**
     * Lazy load configuration files.
     * 
     * @TODO How do we fallback to a default value?
     *
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        // Attempt to lazy load keys.
        if( $this->has($key) === false ){

            $this->load($key);

        }

        return $this->resolve($key);
    }

    /**
     * Add a new key/value pair
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function add(string $key, $value): void
    {
        $this->items[$key] = $value;
    }

    /**
     * Undocumented function
     *
     * @param string $key
     * @return void
     */
    public function load(string $key): void
    {
        if( preg_match("/^([^\.]+)\.?/", $key, $match) ){

            // Save the key
            $key = $match[1];

            // Build the file path
            $file = path("config/{$key}.php");

            $this->loadFile($key, $file);
        }
    }

    /**
     * Load a file and assign it to the key.
     *
     * @param string $key
     * @param string $file
     * @return void
     */
    public function loadFile(string $key, string $file): void
    {
        // Check for file's existence
        if( file_exists($file) === false ){
            throw new \Exception("Config file not found: {$file}");
        }

        // Pull config file in and add values into master config
        $config = require_once $file;
        $this->add($key, $config);
    }
}