<?php

namespace Limber\Tests;

use DateTime;
use Limber\DependencyManager;
use Limber\EmptyStream;
use PHPUnit\Framework\TestCase;

/**
 * @covers Limber\DependencyManager
 */
class DependencyManagerTest extends TestCase
{
	public function test_make_on_class_with_no_constructor(): void
	{
		$dependencyManager = new DependencyManager;

		$instance = $dependencyManager->make(EmptyStream::class);

		$this->assertInstanceOf(
			EmptyStream::class,
			$instance
		);
	}

	public function test_make_on_class_with_constructor(): void
	{
		$dependencyManager = new DependencyManager;

		$instance = $dependencyManager->make(DateTime::class);

		$this->assertInstanceOf(
			DateTime::class,
			$instance
		);
	}

	public function test_make_on_class_with_constructor_and_user_args(): void
	{
		$dependencyManager = new DependencyManager;

		$instance = $dependencyManager->make(
			DateTime::class,
			["time" => "1977-01-28 02:15:00"]
		);

		$this->assertInstanceOf(
			DateTime::class,
			$instance
		);

		$this->assertEquals(
			"1977-01-28T02:15:00+00:00",
			$instance->format("c")
		);
	}
}