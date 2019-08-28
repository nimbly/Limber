<?php

if( !\function_exists('class_method') ){

	/**
	 * Return \callable array of Class and Method represented in string form.
	 *
	 * e.g. "\My\Class\Name@some_method"
	 *
	 * @param string $classMethod
	 * @return callable
	 */
	function class_method(string $classMethod): callable
	{
		if( \preg_match("/^(.+)@(.+)$/", $classMethod, $match) ){

			if( \class_exists($match[1]) ){
				return [new $match[1], $match[2]];
			}
		}

		throw new \Exception("Callable string {$classMethod} could not be resolved.");
	}
}