<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


webgenTest(
	__DIR__ . '/examples/texy-in-latte',
	TEMP_DIR,
	__DIR__ . '/examples/texy-in-latte/expected.output'
);
