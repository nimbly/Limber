<?php

namespace Limber;


class Config
{
    /**
     * Path on disk where config files are held.
     *
     * @var string
     */
    protected $configPath;

    /**
     * Config structure.
     *
     * @var array<string, mixed>
     */
    protected $items = [];

    /**
     * Config constructor.
     *
     * @param array $items
     */
    public function __construct(string $configPath, array $items = [])
    {
        $this->configPath = $configPath;
        $this->items = $items;
    }

    /**
     * Get all config entries loaded.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Resolve the flattened key into the actual value.
     *
     * @param string $key
     * @throws \Exception
     * @return mixed
     */
    protected function resolve(string $key)
    {
        // Set the pointer at the root of the items array
        $pointer = &$this->items;

        /**
         * 
         * Loop through all the parts and see if the key exists.
         * 
         */
        foreach( explode(".", $key) as $part ){

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

        try {

            $configValue = $this->resolve($key);

        } catch ( \Exception $exception ){

            return $default;

        }
        
        return $configValue;
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
     * Load a config path from disk.
     *
     * @param string $key
     * @return void
     */
    public function load(string $key): void
    {
        if( preg_match("/^([^\.]+)\.?/", $key, $match) ){
            $this->loadFile($match[1], "{$this->configPath}/{$match[1]}.php");
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
        $config = include $file;
        $this->add($key, $config);
    }
}