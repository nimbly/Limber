<?php

namespace Limber;

use Limber\Exceptions\ApplicationException;
use Limber\Exceptions\DependencyResolutionException;
use Limber\Exceptions\MethodNotAllowedHttpException;
use Limber\Exceptions\NotFoundHttpException;
use Limber\Middleware\CallableMiddleware;
use Limber\Middleware\PrepareHttpResponse;
use Limber\Middleware\RequestHandler;
use Limber\Router\Router;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionClass;
use ReflectionFunction;
use ReflectionObject;
use ReflectionParameter;
use Throwable;

class Application
{
    /**
     * Router instance.
     *
     * @var Router
     */
	protected $router;

	/**
	 * ContainerInterface instance.
	 *
	 * @var ContainerInterface|null
	 */
	protected $container;

    /**
     * Global middleware.
     *
     * @var array
     */
	protected $middleware = [];

	/**
	 * Registered exception handler.
	 *
	 * @var callable|null
	 */
	protected $exceptionHandler;

    /**
     * Application constructor.
     *
     * @param Router $router
     */
    public function __construct(Router $router)
    {
		$this->router = $router;
	}

	/**
	 * Set a ContainerInterface instance to be used when autowiring route handlers.
	 *
	 * @param ContainerInterface $container
	 * @return void
	 */
	public function setContainer(ContainerInterface $container): void
	{
		$this->container = $container;
	}

    /**
     * Set the global middleware to run.
     *
     * @param array<MiddlewareInterface|callable> $middlewares
     * @return void
     */
    public function setMiddleware(array $middlewares): void
    {
		$this->middleware = $middlewares;
	}

    /**
     * Add a middleware to the stack.
     *
     * @param MiddlewareInterface|callable|string $middleware
     */
    public function addMiddleware($middleware): void
    {
        $this->middleware[] = $middleware;
	}

	/**
	 * Add a default application-level exception handler.
	 *
	 * @param callable $exceptionHandler
	 * @return void
	 */
	public function setExceptionHandler(callable $exceptionHandler): void
	{
		$this->exceptionHandler = $exceptionHandler;
	}

    /**
     * Dispatch a request.
     *
     * @param ServerRequestInterface $request
	 * @throws Throwable
     * @return ResponseInterface
     */
    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
		// Resolve the route now to check for Routed middleware.
		$route = $this->router->resolve($request);

		// Attach Request attributes
		$request = $this->attachRequestAttributes(
			$request,
			$route ? $route->getAttributes() : []
		);

		// Normalize the middlewares to be array<MiddlewareInterface>
		$middleware = $this->normalizeMiddleware(
			\array_merge(
				$this->middleware, // Global user-space middleware
				$route ? $route->getMiddleware() : [], // Route specific middleware
				[PrepareHttpResponse::class] // Application specific middleware
			)
		);

		// Build the request handler chain
		$requestHandler = $this->buildHandlerChain(
			$middleware,
			new RequestHandler(function(ServerRequestInterface $request) use ($route): ResponseInterface {

				try {

					if( empty($route) ){

						$methods = $this->router->getMethods($request);

						// 404 Not Found
						if( empty($methods) ){
							throw new NotFoundHttpException("Route not found");
						}

						// 405 Method Not Allowed
						throw new MethodNotAllowedHttpException($methods);
					}

					$routeHandler = $route->getCallableAction();

					return \call_user_func_array(
						$routeHandler,
						$this->resolveDependencies(
							$this->getParametersForCallable($routeHandler),
							\array_merge([ServerRequestInterface::class => $request], $route->getPathParams($request->getUri()->getPath()))
						)
					);

				} catch( Throwable $exception ){

					return $this->handleException($exception, $request);
				}

			})
		);

		return $requestHandler->handle($request);
	}

	/**
	 * Attach attributes to the request.
	 *
	 * @param ServerRequestInterface $request
	 * @param array<string,mixed> $attributes
	 * @return ServerRequestInterface
	 */
	private function attachRequestAttributes(ServerRequestInterface $request, array $attributes = []): ServerRequestInterface
	{
		foreach( $attributes as $attribute => $value ){
			$request = $request->withAttribute($attribute, $value);
		}

		return $request;
	}

	/**
	 * Normalize the given middlewares into instances of MiddlewareInterface.
	 *
	 * @param array<MiddlewareInterface|callable|string|array<class-string,array<string,string>>> $middlewares
	 * @throws ApplicationException
	 * @return array<MiddlewareInterface>
	 */
	private function normalizeMiddleware(array $middlewares): array
	{
		$normalized_middlewares = [];

		foreach( $middlewares as $index => $middleware ){

			if( \is_callable($middleware) ){
				$middleware = new CallableMiddleware($middleware);
			}

			elseif( \is_string($middleware) && \class_exists($middleware) ){
				$middleware = $this->make($middleware);
			}

			elseif( \is_string($index) && \class_exists($index) && \is_array($middleware) ){
				$middleware = $this->make($index, $middleware);
			}

			if( empty($middleware) || $middleware instanceof MiddlewareInterface === false ){
				throw new ApplicationException("Provided middleware must be a class-string, a \callable, or an instance of Psr\Http\Server\MiddlewareInterface.");
			}

			$normalized_middlewares[] = $middleware;
		}

		return $normalized_middlewares;
	}

	/**
	 * Build a RequestHandler chain out of middleware using provided Kernel as the final RequestHandler.
	 *
	 * @param array<MiddlewareInterface> $middleware
	 * @param RequestHandlerInterface $kernel
	 * @return RequestHandlerInterface
	 */
	private function buildHandlerChain(array $middleware, RequestHandlerInterface $kernel): RequestHandlerInterface
	{
		$middleware = \array_reverse($middleware);

		return \array_reduce($middleware, function(RequestHandlerInterface $handler, MiddlewareInterface $middleware): RequestHandler {

			return new RequestHandler(function(ServerRequestInterface $request) use ($handler, $middleware): ResponseInterface {

				try {

					return $middleware->process($request, $handler);

				}
				catch( Throwable $exception ){

					return $this->handleException($exception, $request);
				}

			});

		}, $kernel);
	}

	/**
	 * Handle a thrown exception by either passing it to user provided exception handler
	 * or throwing it if no handler registered with application.
	 *
	 * @param Throwable $exception
	 * @param ServerRequestInterface $request
	 * @throws Throwable
	 * @return ResponseInterface
	 */
	private function handleException(Throwable $exception, ServerRequestInterface $request): ResponseInterface
	{
		if( $this->exceptionHandler ){
			return \call_user_func($this->exceptionHandler, $exception, $request);
		};

		throw $exception;
	}

	/**
	 * Get the reflection parameters for a callable.
	 *
	 * @param callable $handler
	 * @throws ApplicationException
	 * @return array<ReflectionParameter>
	 */
	private function getParametersForCallable(callable $handler): array
	{
		if( \is_array($handler) ){
			[$class, $method] = $handler;

			/** @psalm-suppress ArgumentTypeCoercion */
			$reflectionClass = new ReflectionClass($class);
			$reflector = $reflectionClass->getMethod($method);
		}

		elseif( \is_object($handler) && \method_exists($handler, "__invoke")) {

			$reflectionObject = new ReflectionObject($handler);
			$reflector = $reflectionObject->getMethod("__invoke");
		}

		elseif( \is_string($handler)) {
			$reflector = new ReflectionFunction($handler);
		}

		else {
			throw new DependencyResolutionException("Limber does not have support for this type of callable.");
		}

		return $reflector->getParameters();
	}

	/**
	 * Resolve an array of reflection parameters into an array of concrete instances/values.
	 *
	 * @param array<ReflectionParameter> $reflectionParameters
	 * @param array<string,mixed> $userArgs Array of user supplied arguments to be fed into dependecy resolution.
	 * @return array<mixed>
	 */
	private function resolveDependencies(array $reflectionParameters, array $userArgs = []): array
	{
		return \array_map(
			/**
			 * @return mixed
			 */
			function(ReflectionParameter $reflectionParameter) use ($userArgs) {

				$parameterName = $reflectionParameter->getName();
				$parameterType = $reflectionParameter->getType();

				// Check parameters for a match by name.
				if( \array_key_exists($parameterName, $userArgs) ){
					return $userArgs[$parameterName];
				}

				// Check container and parameters for a match by type.
				if( $parameterType && !$parameterType->isBuiltin() ) {

					if( $this->container && $this->container->has($parameterType->getName()) ){
						return $this->container->get($parameterType->getName());
					}

					// Try to find in the parameters supplied
					$match = \array_filter(
						$userArgs,
						function($parameter) use ($parameterType) {
							$parameter_type_name = $parameterType->getName();
							return $parameter instanceof $parameter_type_name;
						}
					);

					if( $match ){
						return $match[
							\array_keys($match)[0]
						];
					}

					/**
					 * @psalm-suppress ArgumentTypeCoercion
					 */
					return $this->make($parameterType->getName(), $userArgs);
				}

				// No type or the type is a primitive (built in)
				if( empty($parameterType) || $parameterType->isBuiltin() ){

					// Does parameter offer a default value?
					if( $reflectionParameter->isDefaultValueAvailable() ){
						return $reflectionParameter->getDefaultValue();
					}

					elseif( $reflectionParameter->allowsNull() ){
						return null;
					}
				}

				throw new DependencyResolutionException("Cannot resolve parameter \"{$parameterName}\".");
			},
			$reflectionParameters
		);
	}

	/**
	 * Call a callable with optional given parameters.
	 *
	 * @param callable $callable
	 * @param array<string,mixed> $parameters
	 * @return mixed
	 */
	public function call(callable $callable, array $parameters = [])
	{
		$args = $this->resolveDependencies(
			$this->getParametersForCallable($callable),
			$parameters
		);

		return \call_user_func_array($callable, $args);
	}

	/**
	 * Make an instance of a class using autowiring with values from the container.
	 *
	 * @param class-string $className
	 * @param array<string,mixed> $userArgs
	 * @return object
	 */
	public function make(string $className, array $userArgs = []): object
	{
		if( $this->container &&
			$this->container->has($className) ){
			return $this->container->get($className);
		}

		$reflectionClass = new ReflectionClass($className);

		if( $reflectionClass->isInterface() || $reflectionClass->isAbstract() ){
			throw new DependencyResolutionException("Cannot make an instance of an Interface or Abstract.");
		}

		$constructor = $reflectionClass->getConstructor();

		if( empty($constructor) ){
			return $reflectionClass->newInstance();
		}

		$args = $this->resolveDependencies(
			$constructor->getParameters(),
			$userArgs
		);

		return $reflectionClass->newInstanceArgs($args);
	}

    /**
     * Send a response back to calling client.
     *
     * @param ResponseInterface $response
     * @return void
     */
    public function send(ResponseInterface $response): void
    {
        if( !\headers_sent() ){
            \header(
                \sprintf(
					"HTTP/%s %s %s",
					$response->getProtocolVersion(),
					$response->getStatusCode(),
					$response->getReasonPhrase()
				)
            );

            foreach( $response->getHeaders() as $header => $values ){
				\header(
					\sprintf("%s: %s", $header, \implode(",", $values)),
					false
				);
			}
        }

		if( $response->getStatusCode() !== 204 ){
			echo $response->getBody()->getContents();
		}
    }
}