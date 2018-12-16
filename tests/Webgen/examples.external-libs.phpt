<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


webgenTest(
	__DIR__ . '/examples/external-libs',
	TEMP_DIR,
	__DIR__ . '/examples/external-libs/expected.output'
);
