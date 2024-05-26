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

	public function test_dispatch(): void
	{
		$router = new Router;
		$router->get(
			"/foo",
			function(): ResponseInterface {
				return new Response(
					ResponseStatus::OK,
					"Okay"
				);
			}
		);

		$application = new Application($router);
		$response = $application->dispatch(new ServerRequest("get", "/foo"));

		$this->assertEquals(
			ResponseStatus::OK,
			$response->getStatusCode()
		);

		$this->assertEquals(
			"Okay",
			$response->getBody()->getContents()
		);
	}

	/**
	 * @runInSeparateProcess
	 */
	public function test_send(): void
	{
		$application = new Application(new Router);

		\ob_start();
		$application->send(
			new Response(
				ResponseStatus::CREATED,
				"Limber send() test",
				[
					"Header1" => "Value1",
					"Header2" => "Value2",
				]
			)
		);

		$body = \ob_get_contents();
		\ob_end_clean();

		$this->assertEquals(
			"Limber send() test",
			$body
		);

		$this->assertEquals(
			"201",
			\http_response_code()
		);

		$headers = \xdebug_get_headers();
		$this->assertCount(2, $headers);
		$this->assertEquals("Header1: Value1", $headers[0]);
		$this->assertEquals("Header2: Value2", $headers[1]);
	}

	/**
	 * @runInSeparateProcess
	 */
	public function test_send_does_not_send_contents_on_204_no_content_responses(): void
	{
		$application = new Application(new Router);

		\ob_start();
		$application->send(
			new Response(
				ResponseStatus::NO_CONTENT,
				"Limber send() test",
				[
					"Header1" => "Value1",
					"Header2" => "Value2",
				]
			)
		);

		$body = \ob_get_contents();
		\ob_end_clean();

		$this->assertEmpty($body);
	}
}