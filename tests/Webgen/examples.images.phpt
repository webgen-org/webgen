<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


webgenTest(
	__DIR__ . '/examples/images',
	TEMP_DIR,
	__DIR__ . '/examples/images/expected.output'
);
