<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


webgenTest(
	__DIR__ . '/examples/pagination-repeated-generating',
	TEMP_DIR,
	__DIR__ . '/examples/pagination-repeated-generating/expected.output'
);
