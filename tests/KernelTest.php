<?php

namespace Limber\Tests;

use Capsule\Response;
use Capsule\ResponseStatus;
use Limber\DependencyManager;
use Limber\Exceptions\HandlerException;
use Limber\Kernel;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use ReflectionClass;

/**
 * @covers Limber\Kernel
 * @covers Limber\DependencyManager
 */
class KernelTest extends TestCase
{
	public function test_get_callable_handler_string(): void
	{
		$kernel = new Kernel(
			new DependencyManager
		);

		$reflectionClass = new ReflectionClass($kernel);
		$reflectionMethod = $reflectionClass->getMethod("getCallableHandler");
		$reflectionMethod->setAccessible(true);

		$callableHandler = $reflectionMethod->invokeArgs(
			$kernel,
			["Limber\\Tests\\Fixtures\\HandlerClass@handle"]
		);

		$this->assertTrue(
			\is_callable($callableHandler)
		);
	}

	public function test_get_callable_handler_invokable(): void
	{
		$kernel = new Kernel(
			new DependencyManager
		);

		$reflectionClass = new ReflectionClass($kernel);
		$reflectionMethod = $reflectionClass->getMethod("getCallableHandler");
		$reflectionMethod->setAccessible(true);

		$callableHandler = $reflectionMethod->invokeArgs(
			$kernel,
			["Limber\\Tests\\Fixtures\\InvokableHandler"]
		);

		$this->assertTrue(
			\is_callable($callableHandler)
		);
	}

	public function test_get_callable_handler_unresolvable(): void
	{
		$kernel = new Kernel(
			new DependencyManager
		);

		$reflectionClass = new ReflectionClass($kernel);
		$reflectionMethod = $reflectionClass->getMethod("getCallableHandler");
		$reflectionMethod->setAccessible(true);

		$this->expectException(HandlerException::class);

		$reflectionMethod->invokeArgs(
			$kernel,
			[""]
		);
	}

	public function test_get_callable_handler_closure(): void
	{
		$handler = function(): ResponseInterface {
			return new Response(
				ResponseStatus::OK,
				"OK"
			);
		};

		$kernel = new Kernel(
			new DependencyManager
		);

		$reflectionClass = new ReflectionClass($kernel);
		$reflectionMethod = $reflectionClass->getMethod("getCallableHandler");
		$reflectionMethod->setAccessible(true);

		$this->assertSame(
			$handler,
			$reflectionMethod->invokeArgs($kernel, [$handler])
		);
	}
}