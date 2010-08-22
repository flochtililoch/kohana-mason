<?php defined('SYSPATH') OR die('No direct access allowed.');

return array
(
	APPPATH,												// Application
	CACHEPATH,												// Generated code
	realpath(LIBPATH.'symfony').DIRECTORY_SEPARATOR,		// Symfony components
	realpath(MODPATH.'doctrine').DIRECTORY_SEPARATOR,		// Doctrine module
	realpath(LIBPATH.'doctrine').DIRECTORY_SEPARATOR,		// Doctrine ORM Library
	realpath(MODPATH.'phptal').DIRECTORY_SEPARATOR,			// PHPTAL module
	realpath(LIBPATH.'phptal').DIRECTORY_SEPARATOR,			// PHPTAL Library
	realpath(MODPATH.'component').DIRECTORY_SEPARATOR,		// Component manager module
	realpath(LIBPATH.'cache').DIRECTORY_SEPARATOR,			// Cache module
	realpath(MODPATH.'auth').DIRECTORY_SEPARATOR,			// Auth module extension
	realpath(LIBPATH.'auth').DIRECTORY_SEPARATOR,			// Auth module
	SYSPATH													// Kohana core
);