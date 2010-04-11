<?php defined('SYSPATH') OR die('No direct access allowed.');

return array
(
	APPPATH,												// Application
	CACHEPATH,												// Generated code
	realpath(MODPATH.'doctrine').DIRECTORY_SEPARATOR,		// Doctrine module
	realpath(VENDORPATH.'doctrine').DIRECTORY_SEPARATOR,	// Doctrine ORM Library
	realpath(MODPATH.'phptal').DIRECTORY_SEPARATOR,			// PHPTAL module
	realpath(VENDORPATH.'phptal').DIRECTORY_SEPARATOR,		// PHPTAL Library
	realpath(MODPATH.'component').DIRECTORY_SEPARATOR,		// Component manager module
	realpath(MODPATH.'cache').DIRECTORY_SEPARATOR,			// Cache module extension
	realpath(VENDORPATH.'cache').DIRECTORY_SEPARATOR,		// Cache module
	realpath(MODPATH.'auth').DIRECTORY_SEPARATOR,			// Auth module extension
	realpath(VENDORPATH.'auth').DIRECTORY_SEPARATOR,		// Auth module
	SYSPATH													// Kohana core
);