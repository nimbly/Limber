<?php

namespace Limber;

use Limber\Exceptions\CallableResolutionException;
use Limber\Exceptions\ClassResolutionException;
use Limber\Exceptions\ParameterResolutionException;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionFunction;
use ReflectionObject;
use ReflectionParameter;

class DependencyManager
{
	/**
	 * ContainerInterface instance.
	 *
	 * @var ContainerInterface|null
	 */
	protected $container;

	/**
	 * Resolve constructor.
	 *
	 * @param ContainerInterface|null $container
	 */
	public function __construct(?ContainerInterface $container = null)
	{
		$this->container = $container;
	}

	/**
	 * Set the ContainerInterface instance.
	 *
	 * @param ContainerInterface $container
	 * @return void
	 */
	public function setContainer(ContainerInterface $container): void
	{
		$this->container = $container;
	}

	/**
	 * Call a callable with optional given parameters.
	 *
	 * @param callable $callable
	 * @param array<string,mixed> $parameters
	 * @throws CallableResolutionException
	 * @return mixed
	 */
	public function call(callable $callable, array $parameters = [])
	{
		if( \is_array($callable) ){
			[$class, $method] = $callable;

			/** @psalm-suppress ArgumentTypeCoercion */
			$reflectionClass = new ReflectionClass($class);
			$reflectionMethod = $reflectionClass->getMethod($method);
			$reflectionParameters = $reflectionMethod->getParameters();
		}

		elseif( \is_object($callable) && \method_exists($callable, "__invoke")) {

			$reflectionObject = new ReflectionObject($callable);
			$reflectionMethod = $reflectionObject->getMethod("__invoke");
			$reflectionParameters = $reflectionMethod->getParameters();
		}

		elseif( \is_string($callable)) {
			$reflectionFunction = new ReflectionFunction($callable);
			$reflectionParameters = $reflectionFunction->getParameters();
		}

		else {
			throw new CallableResolutionException("No support for this type of callable.");
		}

		$args = $this->resolveParameters(
			$reflectionParameters,
			$parameters
		);

		/** @psalm-suppress PossiblyInvalidFunctionCall */
		return \call_user_func_array($callable, $args);
	}

	/**
	 * Make an instance of the given class.
	 *
	 * Parameters is a key => value pair of parameters that will be injected into
	 * the constructor if they cannot be resolved.
	 *
	 * @param string $class_name
	 * @param array<string,mixed> $parameters Additional parameters to supply to the constructor.
	 * @throws ClassResolutionException
	 * @return object
	 */
	public function make(string $class_name, array $parameters = []): object
	{
		if( !\class_exists($class_name) ){
			throw new ClassResolutionException("Class \"{$class_name}\" does not exist.");
		}

		// Do some reflection to determine constructor parameters
		/** @psalm-suppress ArgumentTypeCoercion */
		$reflectionClass = new ReflectionClass($class_name);

		$constructor = $reflectionClass->getConstructor();

		if( empty($constructor) ){
			return $reflectionClass->newInstance();
		}

		$args = $this->resolveParameters(
			$constructor->getParameters(),
			$parameters
		);

		return $reflectionClass->newInstanceArgs($args);
	}

	/**
	 * Resolve parameters.
	 *
	 * @param array<ReflectionParameter> $reflectionParameters
	 * @param array<string,mixed> $parameters
	 * @throws ParameterResolutionException
	 * @return array<mixed>
	 */
	protected function resolveParameters(array $reflectionParameters, array $parameters = []): array
	{
		return \array_map(
			/**
			 * @return mixed
			 */
			function(ReflectionParameter $reflectionParameter) use ($parameters) {

				$parameterName = $reflectionParameter->getName();
				$parameterType = $reflectionParameter->getType();

				// Check parameters for a match by name.
				if( \array_key_exists($parameterName, $parameters) ){
					return $parameters[$parameterName];
				}

				// Check container and parameters for a match by type.
				if( $parameterType && !$parameterType->isBuiltin() ) {

					if( $this->container &&
						$this->container->has($parameterType->getName()) ){
						return $this->container->get($parameterType->getName());
					}

					// Try to find in the parameters supplied
					$match = \array_filter(
						$parameters,
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
					return $this->make($parameterType->getName(), $parameters);
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

					return null;

					//dd($reflectionParameter->allowsNull());
				}

				throw new ParameterResolutionException("Cannot resolve parameter \"{$parameterName}\".");
			},
			$reflectionParameters
		);
	}
}