<?php

spl_autoload_register(function ($class_name) {

	$class_path = __DIR__ . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $class_name . '.php');

	if (file_exists($class_path))
	{
		require_once $class_path;
		return;
	}
});
