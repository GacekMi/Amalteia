<?php

// require '.maintenance.php';
define('WWW_DIR', dirname(__FILE__));
define('IMG_DIR', WWW_DIR);

$container = require __DIR__ . '/../app/bootstrap.php';

$container->getByType(Nette\Application\Application::class)
	->run();
