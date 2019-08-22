<?php

namespace Limber\Router;

class Route
{
    /**
     * The full URI.
     *
     * @var string
     */
    protected $uri;

    /**
     * Respond to HTTP methods.
     *
     * @var array<string>
     */
    protected $methods = [];

    /**
     * Route action
     *
     * @var string|callable
     */
    protected $action;

    /**
     * Request scheme (http or https)
     *
     * @var array<string>
     */
    protected $schemes = [];

    /**
     * Request hostname
     *
     * @var array<string>
     */
    protected $hostnames = [];

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
     * Pattern parts
     *
     * @var array<string>
     */
    protected $patternParts = [];

    /**
     * Named URI params in route eg: "id" in books/{id}/authors
     *
     * @var array<string>
     */
    private $namedPathParameters = [];


    /**
     * Route constructor.
     *
     * @param string|array $methods
     * @param string $uri
     * @param string|callable $action
     * @param array $config Additional config data (usually passed in from group settings)
     *
     */
    public function __construct($methods, string $uri, $action, array $config = [])
    {
        // Required bits
        $this->methods = \array_map('strtoupper', \is_string($methods) ? [$methods] : $methods);
        $this->action = $action;
        $this->uri = $this->stripLeadingAndTrailingSlash($uri);

        // Optional
        $this->setSchemes($config['scheme'] ?? []);
        $this->setHostnames($config['hostname'] ?? []);
        $this->setMiddleware($config['middleware'] ?? []);
        $this->setPrefix($config['prefix'] ?? '');
        $this->setNamespace($config['namespace'] ?? '');

        foreach( \explode("/", $this->getUri()) as $part ){

            if( \preg_match('/{([a-z0-9_]+)(?:\:([a-z0-9_]+))?}/i', $part, $match) ){

                if( \in_array($match[1], $this->namedPathParameters) ){
                    throw new \Exception("Path parameter \"{$match[1]}\" already defined for route {$match[0]}");
                }

                // Predefined pattern
                if( isset($match[2]) ){

                    if( ($part = RouterAbstract::getPattern($match[2])) === null ){
                        throw new \Exception("Router pattern not found: {$match[2]}");
                    }
                }

                // Match anything
                else {

                    $part = '[^\/]+';
                }

                $part = "({$part})";

                // Save the named path param
                $this->namedPathParameters[] = $match[1];
            }

            $this->patternParts[] = $part;
        }
    }

    /**
     *
     * SETters
     */

    /**
     * @param string|array $schemes
     * @return Route
     */
    public function setSchemes($schemes): Route
    {
        if( !\is_array($schemes) ){
            $schemes = [$schemes];
        }

        $this->schemes = $schemes;
        return $this;
    }

    /**
     * @param string|array $hostnames
     * @return Route
     */
    public function setHostnames($hostnames): Route
    {
        if( !\is_array($hostnames) ){
            $hostnames = [$hostnames];
        }

        $this->hostnames = $hostnames;

        return $this;
    }

    /**
     * @param string $prefix
     * @return Route
     */
    public function setPrefix(string $prefix): Route
    {
        $this->prefix = $this->stripLeadingAndTrailingSlash($prefix);

        return $this;
    }

    /**
     * Apply middleware to this route
     *
     * @param string|array $middleware
     * @return Route
     */
    public function setMiddleware($middleware): Route
    {
        if( !\is_array($middleware) ){
            $middleware = [$middleware];
        }

        $this->middleware = \array_merge($this->middleware, $middleware);

        return $this;
    }

    /**
     * Set the controller namespace.
     *
     * @param string $namespace
     * @return Route
     */
    public function setNamespace(string $namespace): Route
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
      * Get the full URI.
      *
      * @return string
      */
     public function getUri(): string
     {
         if( $this->prefix ){
             return "{$this->prefix}/{$this->uri}";
         }

         return $this->uri;
     }

    /**
     * Get all schemes this route responds to.
     *
     * @return array
     */
    public function getSchemes(): array
    {
        return $this->schemes;
    }

    /**
     * Get all hostnames this route responds to.
     *
     * @return array
     */
    public function getHostnames(): array
    {
        return $this->hostnames;
    }

    /**
     * Get all methods this route respondes to.
     *
     * @return array<string>
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * Get route action.
     *
     * @return string|callable
     */
    public function getAction()
    {
        if( \is_string($this->action) &&
            $this->namespace ){
            return \trim($this->namespace, '\\') . '\\' . $this->action;
        }

        return $this->action;
    }

    /**
     * Get the URI prefix.
     *
     * @return string
     */
    public function getPrefix(): ?string
    {
        return $this->prefix;
    }

    /**
     * Get all middleware this route should apply.
     *
     * @return array<string>
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * Get namespace of route
     *
     * @return string
     */
    public function getNamespace(): ?string
    {
        return $this->namespace;
    }

    /**
     * Get the regex pattern used to match this route.
     *
     * @return array<string>
     */
    public function getPatternParts(): array
    {
        return $this->patternParts;
    }

    /**
     * Extract the path parameters for the given URI.
     *
     * Makes a key => value pair of path part names and their value.
     *
     * Eg.
     *
     * The route "books/{id}" with an actual URI of "books/1234"
     * will return:
     *
     * [
     *      "id" => "1234",
     * ]
     *
     * These named path params are used during Dependency Injection resolution
     * in the Kernel to pass off to the matched method parameter.
     *
     * @param string $path
     * @return array<string, string>
     */
    public function getPathParams(string $path): array
    {
        $pathParams = [];

        // Build out the regex
        $pattern = \implode("\/", $this->getPatternParts());

        if( \preg_match("/^{$pattern}$/", \trim($path, "/"), $parts) ){

            // Grab all but the first match, because that will always be the full string.
            foreach( \array_slice($parts, 1, \count($parts) - 1) as $i => $param ) {
                $pathParams[$this->namedPathParameters[$i]] = $param;
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
    public function matchScheme($scheme): bool
    {
        if( empty($this->schemes) ){
            return true;
        }

        return \array_search(\strtolower($scheme), \array_map('strtolower', $this->schemes)) !== false;
    }


    /**
     * Does the given hostname match the route's hostname?
     *
     * @param string $hostnames
     * @return bool
     */
    public function matchHostname(string $hostnames): bool
    {
        if( empty($this->hostnames) ){
            return true;
        }

        return \array_search(\strtolower($hostnames), \array_map('strtolower', $this->hostnames)) !== false;
    }

    /**
     * Does the given method match the route's set of methods?
     *
     * @param string $method
     * @return bool
     */
    public function matchMethod(string $method): bool
    {
        return \in_array(\strtoupper($method), $this->methods);
    }

    /**
     * Does the given URI match the route's URI?
     *
     * @param string $uri
     * @return bool
     */
    public function matchUri(string $uri): bool
    {
        $pattern = \implode("\/", $this->patternParts);
        return \preg_match("/^{$pattern}$/i", $this->stripLeadingAndTrailingSlash($uri)) != false;
    }

    /**
     * Normalize URIs by stripping off leading and trailing slashes.
     *
     * @param string $uri
     * @return string
     */
    private function stripLeadingAndTrailingSlash(string $uri): string
    {
        return \trim($uri, '/');
    }
}