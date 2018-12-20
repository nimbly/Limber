<?php

namespace Limber\Router;

class Route
{
    /**
     * Request scheme (http or https)
     *
     * @var string
     */
    protected $scheme;

    /**
     * Request hostname
     *
     * @var string
     */
    protected $hostname;

    /**
     * Controller namespace prefix
     *
     * @var string
     */
    protected $namespace;

    /**
     * Request prefix
     *
     * @var string
     */
    protected $prefix;

    /**
     * Route middlware to apply.
     *
     * @var array
     */
    protected $middleware = [];

    /**
     * Respond to HTTP methods
     *
     * @var array
     */
    protected $methods = [];

    /**
     * Request URI
     *
     * @var string
     */
    protected $uri;

    /**
     * Request URI regex pattern
     *
     * @var string
     */
    protected $pattern;

    /**
     * Route action
     *
     * @var string|callable
     */
    protected $action;

    /**
     * Hydrated params
     *
     * @var array
     */
    public $params = [];

    /**
     * Named URI params in route eg: {id} in books/{id}/authors
     *
     * @var array
     */
    private $namedUriParams = [];


    /**
     * Route constructor.
     *
     * @param array $methods
     * @param string $uri
     * @param string|callable $action
     * @param array $config Additional config data (usually passed in from group settings)
     *
     */
    public function __construct(array $methods, $uri, $action, array $config = [])
    {
        // Required bits
        $this->methods = array_map('strtoupper', $methods);
        $this->uri = $this->stripLeadingAndTrailingSlash($uri);
        $this->action = $action;

        // Optional
        $this->scheme = $config['scheme'] ?? null;
        $this->hostname = $config['hostname'] ?? null;
        $this->prefix = $config['prefix'] ?? null;
        $this->namespace = $config['namespace'] ?? null;
        $this->middleware = $config['middleware'] ?? [];

        // URI prefix
        if( $this->prefix ){
            $this->uri = $this->stripLeadingAndTrailingSlash($config['prefix']) . '/' . $this->uri;
        }

        // Namespace
        if( $this->namespace ){
            if( is_string($this->action) ){
                $this->action = trim($this->namespace, '\\') . '\\' . $this->action;
            }
        }

        // Middleware
        if( $this->middleware ){
            if( !is_array($this->middleware) ){
                $this->middleware = [$this->middleware];
            }
        }

        // Build the URI regex pattern
        $self = $this;
        $this->pattern = preg_replace_callback('/{([a-z_]+)(\:([a-z_]+))?}/', function($match) use ($self) {

            if( in_array($match[1], $this->namedUriParams) ){
                throw new \Exception("Path parameter \"{$match[1]}\" already defined for route {$match[0]}");
            }

            // Predefined pattern
            if( isset($match[2]) ){

                if( ($pattern = Router::getPattern($match[3])) == false ){
                    throw new \Exception('Router pattern not found: ' . $pattern[2]);
                }
            }

            // Match anything
            else {

                $pattern = '[^\/]+';
            }

            $self->namedUriParams[] = $match[1];

            return "({$pattern})";

        }, str_replace('/', '\/', $this->uri));
    }

    /**
     * 
     * SETters
     */

    /**
     * @param $scheme
     * @return $this
     */
    public function setScheme($scheme)
    {
        $this->scheme = $scheme;
        return $this;
    }

    /**
     * @param $hostname
     * @return Route
     */
    public function setHostname($hostname)
    {
        $this->hostname = $hostname;
        return $this;
    }

    /**
     * @param $prefix
     * @return Route
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * Apply middleware to this route
     *
     * @param string|array $middleware
     * @return Route
     */
    public function setMiddleware($middleware)
    {
        if( is_string($middleware) ){
            $middleware = [$middleware];
        }

        $this->middleware = array_merge($this->middleware, $middleware);

        return $this;
    }

    /**
     * Set the controller namespace.
     *
     * @param string $namespace
     * @return Route
     */
    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;
        return $this;
    }


    /**
     * 
     * GETters
     * 
     */

     /**
     * Get all methods this route respondes to.
     *
     * @return array
     */
    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * Get route action.
     *
     * @return string|\Closure
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Get the route prefix.
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }
    
    /**
     * Get all middleware this route should apply.
     *
     * @return array
     */
    public function getMiddleware()
    {
        return $this->middleware;
    }

    /**
     * Get URI of route
     *
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * Get namespace of route
     *
     * @return void
     */
    public function getNamespace()
    {
        return $this->namespace;
    }


    /**
     * Extract the path parameters for this given URI - if the URI matches this route.
     * 
     * @param string $uri
     * @return array
     */
    public function getPathParams($uri)
    {
        $pathParams = [];

        if( ($matches = $this->matchUri($uri)) ){

            foreach( array_slice($matches, 1, count($matches) - 1) as $i => $param )
            {
                $pathParams[$this->namedUriParams[$i]] = $param;
            }
        }

        return $pathParams;
    }

    /**
     * 
     * MATCHing
     * 
     */

    /**
     * Does the given scheme match this route's scheme?
     * 
     * @param string $scheme
     * @return bool
     */
    public function matchScheme($scheme)
    {
        if( empty($this->scheme) ||
            $this->scheme == '*' ){
            return true;
        }

        if( is_array($this->scheme) ){
            return (array_search(strtolower($scheme), array_map('strtolower', $this->scheme)) !== false);
        }

        return strtolower($scheme) == strtolower($this->scheme);
    }


    /**
     * Does the given hostname match the route's hostname?
     * 
     * @param string $hostname
     * @return bool
     */
    public function matchHostname($hostname)
    {
        if( empty($this->hostname) ||
            $this->hostname == '*' ){
            return true;
        }

        if( is_array($this->hostname) ){
            return (array_search(strtolower($hostname), array_map('strtolower', $this->hostname)) !== false);
        }

        return strtolower($hostname) == strtolower($this->hostname);
    }

    /**
     * Does the given method match the route's set of methods?
     *
     * @param string $method
     * @return bool
     */
    public function matchMethod($method)
    {
        return in_array(strtoupper($method), $this->methods);
    }

    

    /**
     * Does the given URI match the route's URI?
     * @param string $uri
     * @return array|bool
     */
    public function matchUri($uri)
    {
        if( preg_match("/^{$this->pattern}$/i", $this->stripLeadingAndTrailingSlash($uri), $matches) ){
            return $matches;
        }

        return false;
    }


    /**
     * Normalize URIs
     *
     * @param string $uri
     * @return string
     */
    private function stripLeadingAndTrailingSlash($uri)
    {
        return trim($uri, '/');
    }
}