<?php

/**
 * Rita autoloader
 */
spl_autoload_register(function ($class_name) {
    $filename = __DIR__ . "/classes/".str_replace("\\", "/", $class_name).".php";
	if(file_exists($filename)) {
		require_once($filename);
	}
});