<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


webgenTest(
	__DIR__ . '/examples/more-layouts',
	TEMP_DIR,
	__DIR__ . '/examples/more-layouts/expected.output'
);
