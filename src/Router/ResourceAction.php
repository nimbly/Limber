<?php

namespace Nimbly\Limber\Router;

enum ResourceAction
{
	case get;
	case list;
	case create;
	case update;
	case delete;
}