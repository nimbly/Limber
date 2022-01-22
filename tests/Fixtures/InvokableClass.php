<?php

namespace Nimbly\Limber\Tests\Fixtures;

class InvokableClass
{
	public function __invoke(string $dependency1, int $dependency2)
	{
		return [$dependency1, $dependency2];
	}
}