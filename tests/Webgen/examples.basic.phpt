<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


webgenTest(
	__DIR__ . '/examples/basic',
	TEMP_DIR,
	__DIR__ . '/examples/basic/expected.output'
);
