<?php

namespace Limber\Tests;

use PHPUnit\Framework\TestCase;

/**
 * @covers ::class_method
 */
class TestFunctions extends TestCase
{
	public function test_class_method()
	{
		$this->assertTrue(
			\is_callable(
				\class_method(TestFunctions::class . "@test_class_method")
			)
		);
	}

	public function test_class_method_not_callable()
	{
		$this->expectException(\Exception::class);
		\class_method("FooClass");
	}
}