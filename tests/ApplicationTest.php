<?php

namespace Limber\Tests;

use Capsule\Response;
use Capsule\ResponseStatus;
use Carton\Container;
use Limber\Application;
use Limber\DependencyManager;
use Limber\ExceptionHandlerInterface;
use Limber\MiddlewareManager;
use Limber\RouteManager;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * @covers Limber\Application
 * @covers Limber\DependencyManager
 * @covers Limber\MiddlewareManager
 * @covers Limber\RouteManager
 * @covers Limber\Router\Engines\DefaultRouter
 */
class ApplicationTest extends TestCase
{
	public function test_route_manager_set_in_constructor(): void
	{
		$routeManager = new RouteManager;

		$application = new Application(
			$routeManager,
			new MiddlewareManager,
			new DependencyManager
		);

		$reflection = new \ReflectionClass($application);

		$property = $reflection->getProperty('routeManager');
		$property->setAccessible(true);

		$this->assertSame(
			$routeManager,
			$property->getValue($application)
		);
	}

	public function test_middleware_manager_set_in_constructor(): void
	{
		$middlewareManager = new MiddlewareManager;

		$application = new Application(
			new RouteManager,
			$middlewareManager,
			new DependencyManager
		);

		$reflection = new \ReflectionClass($application);

		$property = $reflection->getProperty('middlewareManager');
		$property->setAccessible(true);

		$this->assertSame(
			$middlewareManager,
			$property->getValue($application)
		);
	}

	public function test_dependency_manager_set_in_constructor(): void
	{
		$dependencyManager = new DependencyManager;

		$application = new Application(
			new RouteManager,
			new MiddlewareManager,
			$dependencyManager
		);

		$reflection = new \ReflectionClass($application);

		$property = $reflection->getProperty("dependencyManager");
		$property->setAccessible(true);

		$this->assertSame(
			$dependencyManager,
			$property->getValue($application)
		);
	}

	public function test_set_container(): void
	{
		$dependencyManager = new DependencyManager;

		$application = new Application(
			new RouteManager,
			new MiddlewareManager,
			$dependencyManager
		);

		$container = new Container;
		$application->setContainer($container);

		$reflection = new \ReflectionClass($dependencyManager);

		$property = $reflection->getProperty('container');
		$property->setAccessible(true);

		$this->assertSame(
			$container,
			$property->getValue($dependencyManager)
		);
	}

	public function test_set_exception_handler(): void
	{
		$middlewareManager = new MiddlewareManager;

		$application = new Application(
			new RouteManager,
			$middlewareManager,
			new DependencyManager,
		);

		$handler = new class implements ExceptionHandlerInterface {

			public function handle(Throwable $exception): ResponseInterface
			{
				return new Response(500);
			}

		};

		$application->setExceptionHandler($handler);

		$reflection = new \ReflectionClass($middlewareManager);
		$property = $reflection->getProperty('exceptionHandler');
		$property->setAccessible(true);

		$this->assertSame(
			$handler,
			$property->getValue($middlewareManager)
		);
	}

	public function test_send(): void
	{
		$application = Application::make();

		$output = "";
		\ob_start(function(string $buffer) use (&$output){
			$output .= $buffer;
		});

		$application->send(
			new Response(
				ResponseStatus::OK,
				"Limber send() test",
				[
					"Header1" => "Value1"
				]
			)
		);

		$responseData = \ob_get_flush();

		$this->assertEquals("Limber send() test", $responseData);
	}
}