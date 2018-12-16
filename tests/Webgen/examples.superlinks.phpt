<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


webgenTest(
	__DIR__ . '/examples/superlinks',
	TEMP_DIR,
	__DIR__ . '/examples/superlinks/expected.output'
);
