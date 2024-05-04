<?php

namespace Nimbly\Limber\Tests;

use Nimbly\Carton\Container;
use Nimbly\Capsule\Response;
use Nimbly\Capsule\ResponseStatus;
use Nimbly\Capsule\ServerRequest;
use Nimbly\Limber\Application;
use Nimbly\Limber\ExceptionHandlerInterface;
use Nimbly\Limber\Exceptions\MethodNotAllowedHttpException;
use Nimbly\Limber\Exceptions\NotFoundHttpException;
use Nimbly\Limber\Router\Router;
use Nimbly\Limber\Tests\Fixtures\SampleMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionClass;
use Throwable;

/**
 * @covers Nimbly\Limber\Application
 * @covers Nimbly\Limber\Router\Router
 * @covers Nimbly\Limber\Router\Route
 * @covers Nimbly\Limber\Middleware\RequestHandler
 * @covers Nimbly\Limber\Middleware\PrepareHttpResponse
 * @covers Nimbly\Limber\Exceptions\ApplicationException
 * @covers Nimbly\Limber\Exceptions\RouteException
 * @covers Nimbly\Limber\Exceptions\HttpException
 * @covers Nimbly\Limber\Exceptions\MethodNotAllowedHttpException
 * @covers Nimbly\Limber\Exceptions\NotFoundHttpException
 * @covers Nimbly\Limber\EmptyStream
 *
 * @uses Nimbly\Limber\Router\RouterInterface
 */
class ApplicationTest extends TestCase
{
	public function test_constructor(): void
	{
		$router = new Router;
		$middleware = [SampleMiddleware::class];
		$container = new Container;
		$exceptionHandler = new class implements ExceptionHandlerInterface {
			public function handle(Throwable $exception, ServerRequestInterface $request): ResponseInterface
			{
				return new Response(204);
			}
		};

		$application = new Application(
			$router,
			$middleware,
			$container,
			$exceptionHandler);

		$reflection = new ReflectionClass($application);

		$property = $reflection->getProperty("requestHandler");
		$property->setAccessible(true);

		$this->assertNotNull(
			$property->getValue($application)
		);

		$property = $reflection->getProperty("container");
		$property->setAccessible(true);

		$this->assertSame(
			$container,
			$property->getValue($application)
		);
	}

	public function test_send(): void
	{
		$application = new Application(new Router);

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