<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


webgenTest(
	__DIR__ . '/examples/deferred-files',
	TEMP_DIR,
	__DIR__ . '/examples/deferred-files/expected.output'
);
