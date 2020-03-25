<?php

namespace Limber\Tests;

use Limber\Exceptions\BadGatewayHttpException;
use Limber\Exceptions\BadRequestHttpException;
use Limber\Exceptions\ConflictHttpException;
use Limber\Exceptions\ForbiddenHttpException;
use Limber\Exceptions\GatewayTimeoutHttpException;
use Limber\Exceptions\GoneHttpException;
use Limber\Exceptions\InternalServerErrorHttpException;
use Limber\Exceptions\MethodNotAllowedHttpException;
use Limber\Exceptions\NotAcceptableHttpException;
use Limber\Exceptions\NotFoundHttpException;
use Limber\Exceptions\ServiceUnavailableHttpException;
use Limber\Exceptions\TooManyRequestsHttpException;
use Limber\Exceptions\UnauthorizedHttpException;
use Limber\Exceptions\UnprocessableEntityHttpException;
use PHPUnit\Framework\TestCase;

/**
 * @covers Limber\Exceptions\HttpException
 * @covers Limber\Exceptions\BadGatewayHttpException
 * @covers Limber\Exceptions\BadRequestHttpException
 * @covers Limber\Exceptions\ConflictHttpException
 * @covers Limber\Exceptions\ForbiddenHttpException
 * @covers Limber\Exceptions\GatewayTimeoutHttpException
 * @covers Limber\Exceptions\GoneHttpException
 * @covers Limber\Exceptions\InternalServerErrorHttpException
 * @covers Limber\Exceptions\MethodNotAllowedHttpException
 * @covers Limber\Exceptions\NotAcceptableHttpException
 * @covers Limber\Exceptions\NotFoundHttpException
 * @covers Limber\Exceptions\ServiceUnavailableHttpException
 * @covers Limber\Exceptions\TooManyRequestsHttpException
 * @covers Limber\Exceptions\UnauthorizedHttpException
 * @covers Limber\Exceptions\UnprocessableEntityHttpException
 */
class ExceptionTest extends TestCase
{
	public function test_bad_gateway_http_exception(): void
	{
		$exception = new BadGatewayHttpException;
		$this->assertEquals(502, $exception->getHttpStatus());
	}

	public function test_bad_request_http_exception(): void
	{
		$exception = new BadRequestHttpException;
		$this->assertEquals(400, $exception->getHttpStatus());
	}

	public function test_conflict_http_exception(): void
	{
		$exception = new ConflictHttpException;
		$this->assertEquals(409, $exception->getHttpStatus());
	}

	public function test_forbidden_http_exception(): void
	{
		$exception = new ForbiddenHttpException;
		$this->assertEquals(403, $exception->getHttpStatus());
	}

	public function test_gateway_timeout_http_exception(): void
	{
		$exception = new GatewayTimeoutHttpException;
		$this->assertEquals(504, $exception->getHttpStatus());
	}

	public function test_gone_http_exception(): void
	{
		$exception = new GoneHttpException;
		$this->assertEquals(410, $exception->getHttpStatus());
	}

	public function test_internal_server_error_http_exception(): void
	{
		$exception = new InternalServerErrorHttpException;
		$this->assertEquals(500, $exception->getHttpStatus());
	}

	public function test_method_not_allowed_http_exception(): void
	{
		$exception = new MethodNotAllowedHttpException(['GET','POST']);
		$this->assertEquals(405, $exception->getHttpStatus());
		$this->assertEquals([
			'Allow' => 'GET, POST'
		], $exception->getHeaders());
	}

	public function test_not_acceptable_http_exception(): void
	{
		$exception = new NotAcceptableHttpException;
		$this->assertEquals(406, $exception->getHttpStatus());
	}

	public function test_not_found_http_exception(): void
	{
		$exception = new NotFoundHttpException;
		$this->assertEquals(404, $exception->getHttpStatus());
	}

	public function test_service_unavailable_http_exception(): void
	{
		$exception = new ServiceUnavailableHttpException('Wed, 21 Oct 2015 07:28:00 GMT');
		$this->assertEquals(503, $exception->getHttpStatus());
		$this->assertEquals([
			'Retry-After' => 'Wed, 21 Oct 2015 07:28:00 GMT'
		], $exception->getHeaders());
	}

	public function test_too_many_requests_http_exception(): void
	{
		$exception = new TooManyRequestsHttpException('Wed, 21 Oct 2015 07:28:00 GMT');
		$this->assertEquals(429, $exception->getHttpStatus());
		$this->assertEquals([
			'Retry-After' => 'Wed, 21 Oct 2015 07:28:00 GMT'
		], $exception->getHeaders());
	}

	public function test_unauthorized_http_exception(): void
	{
		$exception = new UnauthorizedHttpException('Bearer');
		$this->assertEquals(401, $exception->getHttpStatus());
		$this->assertEquals([
			'WWW-Authenticate' => 'Bearer'
		], $exception->getHeaders());
	}

	public function test_unprocessable_entity_http_exception(): void
	{
		$exception = new UnprocessableEntityHttpException;
		$this->assertEquals(422, $exception->getHttpStatus());
	}
}