<?php

// Define error reporting level
error_reporting(E_ALL | E_STRICT);

// Define the start time and memory usage of the application
define('KOHANA_START_TIME', microtime(TRUE));
define('KOHANA_START_MEMORY', memory_get_usage());

// Define files extension
define('EXT', '.php');

// Define the absolute paths for configured directories
define('DOCROOT', realpath(__DIR__).DIRECTORY_SEPARATOR);
define('VENDORPATH', realpath(DOCROOT.'vendor').DIRECTORY_SEPARATOR);
define('SYSPATH', realpath(VENDORPATH.'kohana').DIRECTORY_SEPARATOR);
define('MODPATH', realpath(DOCROOT.'modules').DIRECTORY_SEPARATOR);
define('VARPATH', realpath(DOCROOT.'var').DIRECTORY_SEPARATOR);
define('APPSPATH', realpath(DOCROOT.'applications').DIRECTORY_SEPARATOR);

// Load the base / Kohana core class / Kohana core extension class
require SYSPATH.'base'.EXT;
require SYSPATH.'classes/kohana/core'.EXT;
require MODPATH.'kohana'.EXT;

// Init app
Kohana::init();