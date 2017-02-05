<?php
	/**
	 * @author		Jan Pecha, <janpecha@email.cz>
	 * @license		http://janpecha.iunas.cz/webgen/#license
	 * @link		http://janpecha.iunas.cz/
	 */

	require __DIR__ . '/libs/loader.php';

	$logger = new Webgen\Logger;
	$runner = new Webgen\Runner($logger);
	$cli = new Webgen\CliParser($logger);

	$cli->prepareEnvironment();
	$cli->run($runner);
