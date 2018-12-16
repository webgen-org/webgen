<?php
	/**
	 * @author		Jan Pecha, <janpecha@email.cz>
	 * @license		http://janpecha.iunas.cz/webgen/#license
	 * @link		http://janpecha.iunas.cz/
	 */

	$vendorDirectory = NULL;

	if (is_file(__DIR__ . '/vendor/autoload.php')) { // standalone tool
		$vendorDirectory = __DIR__ . '/vendor/';

	} elseif (is_file(__DIR__ . '/../../../vendor/autoload.php')) { // package in vendor
		$vendorDirectory = __DIR__ . '/../../';
	}

	if ($vendorDirectory === NULL) {
		die('Missing vendor directory, run `composer install`.');
	}

	require $vendorDirectory . '/autoload.php';

	$logger = new CzProject\Logger\CliLogger;
	$runner = new Webgen\Runner($logger);
	$cli = new Webgen\CliParser($logger, $_SERVER['argv']);

	$cli->prepareEnvironment();
	die($cli->run($runner));
