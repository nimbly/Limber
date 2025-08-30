<?php

namespace Nimbly\Limber;

use Nimbly\Limber\Middleware\PrepareHttpResponse;
use Nimbly\Limber\Middleware\RouteResolver;
use Nimbly\Limber\Router\Router;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

class Application
{
	protected RequestHandlerInterface $requestHandler;

	/**
	 * @param Router $router Router instance with routes defined.
	 * @param array<MiddlewareInterface|class-string|array<class-string,array<string,mixed>>> $middleware Global middleware to apply to all incoming requests.
	 * @param ContainerInterface|null $container ContainerInterface instance with your dependencies already added.
	 * @param ExceptionHandlerInterface|null $exceptionHandler Default/fallback exception handler called within the middleware pipeline.
	 */
	public function __construct(
		Router $router,
		array $middleware = [],
		protected ?ContainerInterface $container = null,
		?ExceptionHandlerInterface $exceptionHandler = null)
	{
		$middlewareManager = new MiddlewareManager($container, $exceptionHandler);

		$this->requestHandler = $middlewareManager->compile(

			middleware: $middlewareManager->normalize(
				\array_merge(
					[
						new PrepareHttpResponse,
						new RouteResolver($router, $middlewareManager),
					],
					\array_reverse($middleware, true)
				)
			),

			kernel: new Kernel($container)
		);
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
		return $this->requestHandler->handle($request);
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
				),
				true,
				$response->getStatusCode()
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