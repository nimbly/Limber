<?php

namespace Limber\Tests\Fixtures;

class ConstructorClass
{
	/**
	 * Parameter 1
	 *
	 * @var string
	 */
	protected $param1;

	public function __construct(string $param1, string $param2 = "param2")
	{
		$this->param1 = $param1;
		$this->param2 = $param2;
	}

	public function getParam1(): string
	{
		return $this->param1;
	}
}