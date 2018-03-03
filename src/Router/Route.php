<?php

namespace Limber\Router;

class Route
{
    public $scheme;
    public $hostname;
    public $namespace;
    public $prefix;
    public $middleware = [];

    public $methods = [];
    public $uri;
    public $pattern;
    public $action;

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
     * @param string|\Callable $action
     * @param array $config Additional config data (usually passed in from group settings)
     *
     */
    public function __construct(array $methods, $uri, $action, array $config = [])
    {
        $this->methods = array_map('strtoupper', $methods);
        $this->uri = $this->stripLeadingAndTrailingSlash($uri);
        $this->action = $action;

        // Scheme
        if( !empty($config['scheme']) ){
            $this->scheme = $config['scheme'];
        }

        // Hostname
        if( !empty($config['hostname']) ){
            $this->hostname = $config['hostname'];
        }

        // URI prefix
        if( !empty($config['prefix']) ){
            $this->prefix($config['prefix']);

            $this->uri = $this->stripLeadingAndTrailingSlash($config['prefix']) . '/' . $this->uri;
        }

        // Namespace
        if( !empty($config['namespace']) ){
            $this->namespace = trim($config['namespace'], '\\');

            if( is_string($this->action) ){
                $this->action = $this->namespace . '\\' . $this->action;
            }
        }

        // Middleware
        if( !empty($config['middleware']) ){

            if( !is_array($config['middleware']) ){
                $config['middleware'] = [$config['middleware']];
            }

            $this->middleware = $config['middleware'];
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
     * @param $scheme
     * @return $this
     */
    public function scheme($scheme)
    {
        $this->scheme = $scheme;
        return $this;
    }

    /**
     * @param $hostname
     * @return Route
     */
    public function hostname($hostname)
    {
        $this->hostname = $hostname;
        return $this;
    }

    /**
     * @param $prefix
     * @return Route
     */
    public function prefix($prefix)
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
    public function middleware($middleware)
    {
        if( is_string($middleware) ){
            $middleware = [$middleware];
        }

        $this->middleware = array_merge($this->middleware, $middleware);

        return $this;
    }

    /**
     * @param $scheme
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
     * @param $hostname
     * @return bool
     */
    public function matchHost($hostname)
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
     * Match HTTP method
     *
     * @param $method
     * @return bool
     */
    public function matchMethod($method)
    {
        return in_array($method, $this->methods);
    }

    /**
     * @param $uri
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
     * @param $uri
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