<?php

namespace Nimbly\Limber;

use Nimbly\Limber\Exceptions\ApplicationException;
use Nimbly\Limber\Exceptions\CallableResolutionException;
use Nimbly\Limber\Exceptions\ClassResolutionException;
use Nimbly\Limber\Exceptions\MethodNotAllowedHttpException;
use Nimbly\Limber\Exceptions\NotFoundHttpException;
use Nimbly\Limber\Exceptions\ParameterResolutionException;
use Nimbly\Limber\Middleware\CallableMiddleware;
use Nimbly\Limber\Middleware\PrepareHttpResponse;
use Nimbly\Limber\Middleware\RequestHandler;
use Nimbly\Limber\Router\Router;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionObject;
use ReflectionParameter;
use Throwable;

class Application
{
	/**
	 * @param Router $router
	 * @param array<class-string|callable|MiddlewareInterface> $middleware
	 * @param ContainerInterface|null $container
	 * @param ExceptionHandlerInterface|null $exceptionHandler
	 */
	public function __construct(
		protected Router $router,
		protected array $middleware = [],
		protected ?ContainerInterface $container = null,
		protected ?ExceptionHandlerInterface $exceptionHandler = null)
	{
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

						$methods = $this->router->getSupportedMethods($request);

						// 404 Not Found
						if( empty($methods) ){
							throw new NotFoundHttpException("Route not found");
						}

						// 405 Method Not Allowed
						throw new MethodNotAllowedHttpException($methods);
					}

					$routeHandler = $this->makeCallable($route->getHandler());

					return \call_user_func_array(
						$routeHandler,
						$this->resolveReflectionParameters(
							$this->getReflectionParametersForCallable($routeHandler),
							\array_merge(
								[ServerRequestInterface::class => $request],
								$route->getPathParameters($request->getUri()->getPath())
							)
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
	private function attachRequestAttributes(ServerRequestInterface $request, array $attributes): ServerRequestInterface
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
		return \array_reduce(
			\array_reverse($middleware),
			function(RequestHandlerInterface $handler, MiddlewareInterface $middleware): RequestHandler {
				return new RequestHandler(
					function(ServerRequestInterface $request) use ($handler, $middleware): ResponseInterface {
						try {

							return $middleware->process($request, $handler);
						}
						catch( Throwable $exception ){
							return $this->handleException($exception, $request);
						}
					}
				);
			},
			$kernel
		);
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
		if( !$this->exceptionHandler ){
			throw $exception;
		};

		return $this->exceptionHandler->handle($exception, $request);
	}

	/**
	 * Get the reflection parameters for a callable.
	 *
	 * @param callable $handler
	 * @throws ParameterResolutionException
	 * @return array<ReflectionParameter>
	 */
	private function getReflectionParametersForCallable(callable $handler): array
	{
		if( \is_array($handler) ){
			[$class, $method] = $handler;

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
			throw new ParameterResolutionException("Given handler is not callable.");
		}

		return $reflector->getParameters();
	}

	/**
	 * Resolve an array of reflection parameters into an array of concrete instances/values.
	 *
	 * This will resolve dependencies from:
	 * 		o The ContainerInterface instance (if any)
	 * 		o The $parameters array
	 * 		o Try to recursively make() new instances
	 * 		o Default values provided by method/function signature
	 *
	 * @param array<ReflectionParameter> $reflection_parameters
	 * @param array<string,mixed> $parameters Additional named parameters and values to use during dependency resolution.
	 * @throws ParameterResolutionException
	 * @throws ClassResolutionException
	 * @return array<mixed> All resolved parameters in the order they appeared in $reflection_parameters
	 */
	private function resolveReflectionParameters(array $reflection_parameters, array $parameters = []): array
	{
		return \array_map(
			/**
			 * @return mixed
			 */
			function(ReflectionParameter $reflectionParameter) use ($parameters) {

				$parameter_name = $reflectionParameter->getName();

				// Check user arguments for a match by name.
				if( \array_key_exists($parameter_name, $parameters) ){
					return $parameters[$parameter_name];
				}

				$parameter_type = $reflectionParameter->getType();

				if( $parameter_type instanceof \ReflectionNamedType === false ) {
					throw new ParameterResolutionException("Cannot resolve union or intersection types");
				}

				/**
				 * Check container and parameters for a match by type.
				 */
				if( !$parameter_type->isBuiltin() ) {

					if( $this->container && $this->container->has($parameter_type->getName()) ){
						return $this->container->get($parameter_type->getName());
					}

					// Try to find in the parameters supplied
					$match = \array_filter(
						$parameters,
						function($parameter) use ($parameter_type): bool {
							$parameter_type_name = $parameter_type->getName();
							return $parameter instanceof $parameter_type_name;
						}
					);

					if( $match ){
						return $match[
							\array_keys($match)[0]
						];
					}

					try {

						return $this->make(
							$parameter_type->getName(),
							$parameters
						);
					}
					catch( \Exception $exception ){}
				}

				/**
				 * If a default value is defined, use that, including a null value.
				 */
				if( $reflectionParameter->isDefaultValueAvailable() ){
					return $reflectionParameter->getDefaultValue();
				}
				elseif( $reflectionParameter->allowsNull() ){
					return null;
				}

				if( !empty($exception) ){
					throw $exception;
				}

				throw new ParameterResolutionException("Cannot resolve parameter \"{$parameter_name}\".");
			},
			$reflection_parameters
		);
	}

	/**
	 * Try to make a thing callable.
	 *
	 * You can pass something that PHP considers "callable" OR a string that represents
	 * a callable in the format: \Fully\Qualiafied\Namespace@methodName.
	 *
	 * @param string|callable $thing
	 * @throws ParameterResolutionException
	 * @throws ClassResolutionException
	 * @throws CallableResolutionException
	 * @return callable
	 */
	private function makeCallable(string|callable $thing): callable
	{
		if( \is_string($thing) ){

			if( \class_exists($thing) ){
				$thing = $this->make($thing);
			}

			elseif( \preg_match("/^(.+)@(.+)$/", $thing, $match) ){
				if( \class_exists($match[1]) ){
					$thing = [$this->make($match[1]), $match[2]];
				}
			}
		}

		if( !\is_callable($thing) ){
			throw new CallableResolutionException("Cannot make callable");
		}

		return $thing;
	}

	/**
	 * Call a callable with optional given parameters.
	 *
	 * @param callable $callable
	 * @param array<string,mixed> $parameters Additional named parameters and values to use during dependency resolution.
	 * @return mixed
	 */
	public function call(callable $callable, array $parameters = [])
	{
		$args = $this->resolveReflectionParameters(
			$this->getReflectionParametersForCallable($callable),
			$parameters
		);

		return \call_user_func_array($callable, $args);
	}

	/**
	 * Make an instance of a class using autowiring with values from the container.
	 *
	 * @param string $class_name Fully qualified namespace of class to make.
	 * @param array<string,mixed> $parameters Additional named parameters and values to use during dependency resolution.
	 * @throws ParameterResolutionException
	 * @throws ClassResolutionException
	 * @return object
	 */
	public function make(string $class_name, array $parameters = []): object
	{
		if( $this->container &&
			$this->container->has($class_name) ){
			return $this->container->get($class_name);
		}

		try {

			/**
		 	* @psalm-suppress ArgumentTypeCoercion
		 	*/
			$reflectionClass = new ReflectionClass($class_name);
		}
		catch( ReflectionException $reflectionException ){
			throw new ClassResolutionException(
				$reflectionException->getMessage(),
				$reflectionException->getCode(),
				$reflectionException
			);
		}

		if( $reflectionClass->isInterface() || $reflectionClass->isAbstract() ){
			throw new ClassResolutionException("Cannot make an instance of an Interface or Abstract.");
		}

		$constructor = $reflectionClass->getConstructor();

		if( empty($constructor) ){
			return $reflectionClass->newInstance();
		}

		$resolved_arguments = $this->resolveReflectionParameters(
			$constructor->getParameters(),
			$parameters
		);

		return $reflectionClass->newInstanceArgs($resolved_arguments);
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