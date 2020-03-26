<?php

namespace Limber;

use Limber\Exceptions\ApplicationException;
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
use ReflectionMethod;
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

					return $this->handleException($exception);
				}

			})
		);

		return $requestHandler->handle($request);
	}

	/**
	 * Normalize the given middlewares into instances of MiddlewareInterface.
	 *
	 * @param array<MiddlewareInterface|callable|string> $middlewares
	 * @throws ApplicationException
	 * @return array<MiddlewareInterface>
	 */
	private function normalizeMiddleware(array $middlewares): array
	{
		return \array_map(function($middleware): MiddlewareInterface {

			if( \is_callable($middleware) ){
				$middleware = new CallableMiddleware($middleware);
			}

			if( \is_string($middleware) &&
				\class_exists($middleware) ){
				$middleware = new $middleware;
			}

			if( $middleware instanceof MiddlewareInterface === false ){
				throw new ApplicationException("Provided middleware must be a string, a \callable, or an instance of Psr\Http\Server\MiddlewareInterface.");
			}

			return $middleware;

		}, $middlewares);
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

					return $this->handleException($exception);
				}

			});

		}, $kernel);
	}

	/**
	 * Handle a thrown exception by either passing it to user provided exception handler
	 * or throwing it if no handler registered with application.
	 *
	 * @param Throwable $exception
	 * @throws Throwable
	 * @return ResponseInterface
	 */
	private function handleException(Throwable $exception): ResponseInterface
	{
		if( $this->exceptionHandler ){
			return \call_user_func($this->exceptionHandler, $exception);
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
		if( \is_array($handler) ) {
			$reflector = new ReflectionMethod($handler[0], $handler[1]);
		}
		else {
			/**
			 * @psalm-suppress ArgumentTypeCoercion
			 */
			$reflector = new ReflectionFunction($handler);
		}

		return $reflector->getParameters();
	}

	/**
	 * Resolve an array of reflection parameters into an array of concrete instances/values indexed by parameter name and value.
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

				// No type or the type is a primitive (built in)
				if( empty($parameterType) || $parameterType->isBuiltin() ){

					// Check in user supplied argument list first.
					if( \array_key_exists($parameterName, $userArgs) ){
						return $userArgs[$parameterName];
					}

					// Does parameter offer a default value?
					elseif( $reflectionParameter->isDefaultValueAvailable() ){
						return $reflectionParameter->getDefaultValue();
					}

					elseif( $reflectionParameter->isOptional() || $reflectionParameter->allowsNull() ){
						return null;
					}
				}

				// Parameter type is a class
				else {

					if( $this->container && $this->container->has($parameterType->getName()) ){
						return $this->container->get($parameterType->getName());
					}
					elseif( isset($userArgs[ServerRequestInterface::class]) &&
						\is_a($userArgs[ServerRequestInterface::class], $parameterType->getName()) ){
						return $userArgs[ServerRequestInterface::class];
					}
					else {

						/**
						 * @psalm-suppress ArgumentTypeCoercion
						 */
						return $this->make($parameterType->getName(), $userArgs);
					}
				}

				throw new ApplicationException("Autowiring failed: Cannot resolve for " . $parameterName . "<" . ($parameterType ? $parameterType->getName() : "none") . ">.");
			},
			$reflectionParameters
		);
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
			throw new ApplicationException("Cannot make an instance of an Interface or Abstract.");
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
                \sprintf("HTTP/%s %s %s", $response->getProtocolVersion(), $response->getStatusCode(), $response->getReasonPhrase())
            );

            foreach( $response->getHeaders() as $header => $values ){
                foreach( $values as $value ){
                    \header(
						\sprintf("%s: %s", $header, $value),
						false
                    );
                }
			}
        }

		if( $response->getStatusCode() !== 204 ){
			echo $response->getBody()->getContents();
		}
    }
}