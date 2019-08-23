<?php

if( !\function_exists('class_method') ){

	/**
	 * Return \callable array of Class and Method represented in string form.
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

		throw new \Exception("Class {$match[1]} could not be found.");
	}
}